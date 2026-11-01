#!/usr/bin/env node

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { createHash, randomBytes } from "node:crypto";
import { spawn } from "node:child_process";
import { mkdir, open, readFile, stat, unlink, writeFile } from "node:fs/promises";
import { createServer as createHttpServer } from "node:http";
import { createConnection, createServer as createIpcServer } from "node:net";
import { homedir, tmpdir } from "node:os";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import WebSocket, { WebSocketServer } from "ws";
import { z } from "zod";

const BRIDGE_PACKAGE_NAME = "vormbyte-visual-mcp";
const BRIDGE_PACKAGE_VERSION = "0.1.2";
const WORKSPACE_PROTOCOL_NAME = "vormbyte-visual-workspace";
const WORKSPACE_PROTOCOL_VERSION = 1;
const REQUIRED_WORKSPACE_PROTOCOL_CAPABILITIES = ["workspace-request-v1"];
const DAEMON_CONNECT_TIMEOUT_MS = 1000;
const DAEMON_START_TIMEOUT_MS = 8000;
const DAEMON_CONNECT_RETRY_MS = 100;
const DAEMON_START_LOCK_STALE_MS = 15000;
const DAEMON_IDLE_TIMEOUT_MS = 5000;
const DAEMON_STARTUP_IDLE_TIMEOUT_MS = 30000;
const DAEMON_SOCKET_PATH_MAX_BYTES = 100;
const INTERNAL_CONNECTION_INSTRUCTIONS = "__vormbyte_connection_instructions";
const INTERNAL_WORKSPACE_STATUS = "__vormbyte_workspace_status";

let host = "127.0.0.1";
let port = 0;
let token = "";
let timeoutMs = 30000;
let workspaceUrl = "";
let actualPort = 0;
let connectionString = "";
let controlSocketPath = "";
let workspaceSocket = null;
let workspaceProtocolValidated = false;
let workspaceRequestSeq = 1;
let wss = null;
let httpServer = null;
let controlServer = null;
let shuttingDown = false;
let idleShutdownTimer = null;
let daemonReady = false;
let resolveDaemonReady = () => {};
let daemonReadyPromise = new Promise((resolve) => {
  resolveDaemonReady = resolve;
});
const workspacePending = new Map();
const agentSockets = new Set();

const args = parseArgs(process.argv.slice(2));
try {
  if (args.daemon === "true") {
    await runDaemon(args);
  } else {
    await runClient(args);
  }
} catch (error) {
  process.stderr.write(
    `[vormbyte-visual] ${error instanceof Error ? error.message : String(error)}\n`
  );
  process.exit(1);
}

async function runClient(rawArgs) {
  const config = resolveConfig(rawArgs, await readState(configuredWorkspaceUrl(rawArgs)));
  const daemonSocket = await ensureDaemonConnection(config);
  const daemonRpc = createDaemonRpcClient(daemonSocket);

  const server = new McpServer({
    name: "vormbyte-visual-workspace",
    version: BRIDGE_PACKAGE_VERSION,
  });

  server.registerTool(
    "workspace_connection_info",
    {
      title: "Get dashboard connection string",
      description:
        "Return the exact message the assistant should show the user to connect Vormbyte Visual.",
    },
    async () => ({
      content: [
        {
          type: "text",
          text: String(await daemonRpc.call(INTERNAL_CONNECTION_INSTRUCTIONS, {})),
        },
      ],
    })
  );

  server.registerTool(
    "workspace_status",
    {
      title: "Get workspace status",
      description:
        "Check whether Vormbyte Visual is connected. If it is not connected, this tool returns explicit user-facing connection instructions.",
    },
    async () => {
      const result = await daemonRpc.call(INTERNAL_WORKSPACE_STATUS, {});
      if (isPlainObject(result) && result.kind === "text" && typeof result.text === "string") {
        return {
          content: [
            {
              type: "text",
              text: result.text,
            },
          ],
        };
      }

      return textResult(result);
    }
  );

  server.registerTool(
    "workspace_request",
    {
      title: "Send workspace request",
      description:
        "Send a request to the connected Vormbyte Visual. Use workspace_status to discover current state and plugin-provided methods or guidance.",
      inputSchema: {
        method: z.string().describe("Workspace method name provided by the plugin dashboard."),
        params: z.record(z.string(), z.unknown()).optional().describe("Method parameters."),
        parameters: z.record(z.string(), z.unknown()).optional().describe("Alias for params, for MCP clients that label tool arguments as parameters."),
        pageId: z.string().optional().describe("Page id for page-scoped workspace methods."),
        toolName: z.string().optional().describe("Page tool name for workspace_call_page_tool."),
        args: z.record(z.string(), z.unknown()).optional().describe("Page tool args for workspace_call_page_tool."),
        reason: z.string().optional().describe("One short line, posed as a question for the user, explaining why an approved backend tool, page tool, or page interaction should run."),
        actionId: z.string().optional().describe("Action id for action status methods."),
        timeoutMs: z.number().optional().describe("Timeout in milliseconds for workspace_wait_action."),
      },
    },
    async (input) => textResult(await daemonRpc.call(input.method, workspaceParams(input)))
  );

  const shutdownClient = () => {
    if (shuttingDown) return;
    shuttingDown = true;
    daemonRpc.close("MCP client shutting down");
    setTimeout(() => process.exit(0), 50).unref();
  };

  process.stdin.on("close", shutdownClient);
  process.stdin.on("end", shutdownClient);
  process.on("SIGINT", shutdownClient);
  process.on("SIGTERM", shutdownClient);

  try {
    await server.connect(new StdioServerTransport());
  } catch (error) {
    process.stderr.write(
      `[vormbyte-visual] Failed to start workspace MCP client: ${
        error instanceof Error ? error.message : String(error)
      }\n`
    );
    process.exit(1);
  }
}

async function runDaemon(rawArgs) {
  const config = resolveConfig(rawArgs, await readState(configuredWorkspaceUrl(rawArgs)));
  host = config.host;
  port = config.port;
  token = config.token;
  timeoutMs = config.timeoutMs;
  workspaceUrl = config.workspaceUrl;
  controlSocketPath = daemonSocketPath(workspaceUrl);

  try {
    controlServer = await createControlServer(controlSocketPath);
    ({ wss, httpServer } = await createBridgeServer({
      host,
      port,
      allowPortFallback: !config.hasExplicitPort && port !== 0,
    }));
  } catch (error) {
    await closeControlServer();
    process.stderr.write(
      `[vormbyte-visual] Failed to start workspace daemon: ${
        error instanceof Error ? error.message : String(error)
      }\n`
    );
    process.exit(1);
  }

  const address = wss.address();
  actualPort = typeof address === "object" && address ? address.port : port;
  connectionString = `ws://${host}:${actualPort}?token=${encodeURIComponent(token)}`;
  writeState({ port: actualPort, token, workspaceUrl: workspaceUrl || undefined }, workspaceUrl).catch((error) => {
    log(`Failed to save workspace connection state: ${error.message}`);
  });
  daemonReady = true;
  resolveDaemonReady();
  announce(connectionAnnouncement());

  wss.on("connection", (socket, request) => {
    const url = new URL(request.url ?? "/", `ws://${request.headers.host ?? `${host}:${port}`}`);
    const role = url.searchParams.get("role") || "workspace";

    if (role !== "workspace") {
      socket.close(1008, "Unsupported role");
      return;
    }

    acceptWorkspaceConnection(socket);
  });

  wss.on("error", (error) => {
    process.stderr.write(`[vormbyte-visual] WebSocket daemon error: ${error.message}\n`);
    process.exitCode = 1;
  });

  httpServer.on("error", (error) => {
    process.stderr.write(`[vormbyte-visual] HTTP daemon error: ${error.message}\n`);
    process.exitCode = 1;
  });

  process.on("SIGINT", shutdownDaemon);
  process.on("SIGTERM", shutdownDaemon);
  scheduleIdleShutdown(DAEMON_STARTUP_IDLE_TIMEOUT_MS);
}

function acceptAgentConnection(socket) {
  agentSockets.add(socket);
  cancelIdleShutdown();
  log(`Agent connected (${agentSockets.size} active).`);

  socket.setEncoding("utf8");
  let buffer = "";
  socket.on("data", (chunk) => {
    buffer += chunk;

    let newlineIndex = buffer.indexOf("\n");
    while (newlineIndex !== -1) {
      const line = buffer.slice(0, newlineIndex).trim();
      buffer = buffer.slice(newlineIndex + 1);
      if (line) {
        handleAgentMessage(socket, line).catch((error) => log(`Agent message error: ${error.message}`));
      }
      newlineIndex = buffer.indexOf("\n");
    }
  });

  socket.on("error", (error) => {
    log(`Agent socket error: ${error.message}`);
  });

  socket.on("close", () => {
    agentSockets.delete(socket);
    log(`Agent disconnected (${agentSockets.size} active).`);
    if (agentSockets.size === 0) {
      scheduleIdleShutdown(DAEMON_IDLE_TIMEOUT_MS);
    }
  });
}

function acceptWorkspaceConnection(socket) {
  if (workspaceSocket && workspaceSocket.readyState === WebSocket.OPEN) {
    workspaceSocket.close(1000, "Replaced by a newer workspace connection");
  }

  workspaceSocket = socket;
  workspaceProtocolValidated = false;
  log("Vormbyte Visual connected.");

  socket.on("message", (data) => {
    handleWorkspaceMessage(data).catch((error) => log(`Workspace message error: ${error.message}`));
  });

  socket.on("close", () => {
    if (workspaceSocket === socket) {
      workspaceSocket = null;
      workspaceProtocolValidated = false;
      rejectAllWorkspacePending("Vormbyte Visual disconnected.");
      log("Vormbyte Visual disconnected.");
    }
  });
}

function scheduleIdleShutdown(delayMs) {
  if (agentSockets.size > 0 || shuttingDown) return;
  cancelIdleShutdown();
  idleShutdownTimer = setTimeout(() => {
    if (agentSockets.size === 0) {
      shutdownDaemon();
    }
  }, delayMs);
  idleShutdownTimer.unref();
}

function cancelIdleShutdown() {
  if (!idleShutdownTimer) return;
  clearTimeout(idleShutdownTimer);
  idleShutdownTimer = null;
}

function shutdownDaemon() {
  if (shuttingDown) return;
  shuttingDown = true;
  cancelIdleShutdown();

  if (workspaceSocket && workspaceSocket.readyState === WebSocket.OPEN) {
    workspaceSocket.close(1001, "MCP daemon shutting down");
  }

  for (const socket of agentSockets) {
    if (!socket.destroyed) {
      socket.end();
    }
  }
  agentSockets.clear();
  rejectAllWorkspacePending("Vormbyte Visual daemon shut down.");

  if (wss) {
    wss.close();
  }

  const closePromises = [];
  if (httpServer) {
    closePromises.push(closeServer(httpServer));
  }
  closePromises.push(closeControlServer());

  Promise.allSettled(closePromises).then(() => process.exit(0));
  setTimeout(() => process.exit(0), 500).unref();
}

async function handleAgentMessage(socket, data) {
  const message = JSON.parse(String(data));
  if (message.type !== "request") return;

  try {
    const result = await handleAgentRequest(message.method, message.params);
    sendAgentResponse(socket, { type: "response", id: message.id, ok: true, result });
  } catch (error) {
    sendAgentResponse(socket, {
      type: "response",
      id: message.id,
      ok: false,
      error: error instanceof Error ? error.message : String(error),
    });
  }
}

async function handleAgentRequest(method, params) {
  if (!daemonReady) {
    await daemonReadyPromise;
  }

  if (method === INTERNAL_CONNECTION_INSTRUCTIONS) {
    return connectionInstructions();
  }

  if (method === INTERNAL_WORKSPACE_STATUS) {
    return workspaceStatusResult();
  }

  return workspaceCall(method, params);
}

function sendAgentResponse(socket, response) {
  if (socket.destroyed || !socket.writable) return;
  socket.write(`${JSON.stringify(response)}\n`);
}

function broadcastAgentEvent(name, payload) {
  const message = JSON.stringify({ type: "event", name, payload });
  for (const socket of agentSockets) {
    if (!socket.destroyed && socket.writable) {
      socket.write(`${message}\n`);
    }
  }
}

async function workspaceStatusResult() {
  const status = {
    connectionString,
    connectionCode: workspaceUrl ? workspaceConnectionCode() : null,
    connectionUrl: workspaceUrl ? workspaceConnectionUrl() : null,
    bridge: bridgeStatus(),
    dashboardConnected: Boolean(workspaceSocket && workspaceSocket.readyState === WebSocket.OPEN),
    compatibility: null,
    workspace: null,
  };

  if (!status.dashboardConnected) {
    return {
      kind: "text",
      text:
        "dashboardConnected: false\n\n" +
        `bridge: ${JSON.stringify(status.bridge, null, 2)}\n\n` +
        "You cannot open, inspect, or edit WordPress pages through this MCP server until the user connects the dashboard.\n\n" +
        connectionInstructions(),
    };
  }

  const workspace = await workspaceCall("workspace_status", {}, { skipCompatibilityCheck: true });
  const compatibility = workspaceCompatibility(workspace);
  workspaceProtocolValidated = compatibility.ok;

  return {
    ...status,
    compatibility,
    workspace,
  };
}

async function ensureDaemonConnection(config) {
  try {
    return await connectAgentSocket(config);
  } catch {
    // The per-site control endpoint may not exist yet, or it may be stale after a crash.
  }

  return withDaemonStartLock(config, async () => {
    try {
      return await connectAgentSocket(config);
    } catch {
      // Start a daemon when no compatible daemon is listening on the per-site control endpoint.
    }

    try {
      await cleanupStaleControlSocket(daemonSocketPath(config.workspaceUrl));
    } catch (error) {
      if (error?.code === "EADDRINUSE") {
        return connectAgentSocket(config);
      }
      throw error;
    }

    startDaemonProcess(config);
    return waitForDaemon(config);
  });
}

async function waitForDaemon(config) {
  const startedAt = Date.now();
  let lastError = null;

  while (Date.now() - startedAt < DAEMON_START_TIMEOUT_MS) {
    try {
      return await connectAgentSocket(config);
    } catch (error) {
      lastError = error;
    }

    await sleep(DAEMON_CONNECT_RETRY_MS);
  }

  throw new Error(
    `Failed to connect to the Vormbyte Visual daemon${
      lastError instanceof Error ? `: ${lastError.message}` : "."
    }`
  );
}

function startDaemonProcess(config) {
  const scriptPath = fileURLToPath(import.meta.url);
  const daemonArgs = [
    scriptPath,
    "--daemon",
    "--host",
    config.host,
    "--port",
    String(config.port),
    "--token",
    config.token,
    "--timeout",
    String(config.timeoutMs),
  ];

  if (config.workspaceUrl) {
    daemonArgs.push("--workspace-url", config.workspaceUrl);
  }

  const child = spawn(process.execPath, daemonArgs, {
    cwd: process.cwd(),
    detached: true,
    env: process.env,
    stdio: "ignore",
  });
  child.unref();
}

async function withDaemonStartLock(config, run) {
  const lockPath = startLockFilePath(config.workspaceUrl);
  await mkdir(dirname(lockPath), { recursive: true, mode: 0o700 });

  while (true) {
    let handle = null;
    try {
      handle = await open(lockPath, "wx", 0o600);
      try {
        return await run();
      } finally {
        await handle.close();
        await unlink(lockPath).catch(() => {});
      }
    } catch (error) {
      if (handle) {
        await handle.close().catch(() => {});
      }

      if (error?.code !== "EEXIST") {
        throw error;
      }

      if (await isStaleStartLock(lockPath)) {
        await unlink(lockPath).catch(() => {});
      }

      await sleep(DAEMON_CONNECT_RETRY_MS);
      try {
        return await connectAgentSocket(config);
      } catch {
        // Another process may still be starting the daemon.
      }
    }
  }
}

async function isStaleStartLock(lockPath) {
  try {
    const stats = await stat(lockPath);
    return Date.now() - stats.mtimeMs > DAEMON_START_LOCK_STALE_MS;
  } catch {
    return false;
  }
}

function connectAgentSocket(config) {
  return connectControlSocketPath(daemonSocketPath(config.workspaceUrl), DAEMON_CONNECT_TIMEOUT_MS);
}

function createDaemonRpcClient(socket) {
  let requestSeq = 1;
  const pending = new Map();
  let buffer = "";

  socket.setEncoding("utf8");
  socket.on("data", (chunk) => {
    buffer += chunk;

    let newlineIndex = buffer.indexOf("\n");
    while (newlineIndex !== -1) {
      const line = buffer.slice(0, newlineIndex).trim();
      buffer = buffer.slice(newlineIndex + 1);
      if (line) {
        handleDaemonMessage(line);
      }
      newlineIndex = buffer.indexOf("\n");
    }
  });

  socket.on("close", () => {
    rejectPending(pending, "Vormbyte Visual daemon disconnected.");
  });

  socket.on("error", (error) => {
    rejectPending(pending, `Vormbyte Visual daemon error: ${error.message}`);
  });

  return {
    call(method, params) {
      if (socket.destroyed || !socket.writable) {
        return Promise.reject(new Error("Vormbyte Visual daemon is not connected."));
      }

      const id = requestSeq++;
      const requestTimeoutMs = workspaceRequestTimeoutMs(method, params);
      const promise = new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          pending.delete(id);
          reject(new Error(`Vormbyte Visual daemon request timed out after ${requestTimeoutMs}ms: ${method}`));
        }, requestTimeoutMs);

        pending.set(id, { resolve, reject, timeout });
      });

      socket.write(`${JSON.stringify({ type: "request", id, method, params: params ?? {} })}\n`);
      return promise;
    },
    close(reason) {
      rejectPending(pending, reason);
      if (!socket.destroyed) {
        socket.end();
      }
    },
  };

  function handleDaemonMessage(line) {
    let message;
    try {
      message = JSON.parse(line);
    } catch (error) {
      log(`Daemon message parse error: ${error instanceof Error ? error.message : String(error)}`);
      return;
    }

    if (message.type === "event") {
      log(`Workspace event: ${message.name}`);
      return;
    }

    if (message.type !== "response") return;

    const entry = pending.get(message.id);
    if (!entry) return;

    pending.delete(message.id);
    clearTimeout(entry.timeout);

    if (message.ok) {
      entry.resolve(message.result ?? null);
    } else {
      entry.reject(new Error(String(message.error ?? "Vormbyte Visual daemon request failed.")));
    }
  }
}

function rejectPending(pending, reason) {
  for (const [id, entry] of pending.entries()) {
    pending.delete(id);
    clearTimeout(entry.timeout);
    entry.reject(new Error(reason));
  }
}

function configuredWorkspaceUrl(rawArgs) {
  return String(rawArgs["workspace-url"] ?? process.env.VORMBYTE_VISUAL_WORKSPACE_URL ?? "").trim();
}

function resolveConfig(rawArgs, state = {}) {
  const hasExplicitPort =
    rawArgs.port !== undefined ||
    process.env.VORMBYTE_VISUAL_WORKSPACE_PORT !== undefined;
  const explicitWorkspaceUrl = configuredWorkspaceUrl(rawArgs);
  const nextPort = Number(rawArgs.port ?? process.env.VORMBYTE_VISUAL_WORKSPACE_PORT ?? state.port ?? 0);
  const nextHost = String(rawArgs.host ?? process.env.VORMBYTE_VISUAL_WORKSPACE_HOST ?? "127.0.0.1");
  const nextToken = String(rawArgs.token ?? process.env.VORMBYTE_VISUAL_WORKSPACE_TOKEN ?? state.token ?? randomBytes(18).toString("hex"));
  const nextTimeoutMs = Number(rawArgs.timeout ?? process.env.VORMBYTE_VISUAL_WORKSPACE_TIMEOUT ?? 30000);
  const nextWorkspaceUrlRaw = explicitWorkspaceUrl || state.workspaceUrl || "";

  if (!Number.isInteger(nextPort) || nextPort < 0 || nextPort > 65535) {
    throw new Error(`Invalid --port value: ${String(rawArgs.port)}`);
  }

  if (!Number.isFinite(nextTimeoutMs) || nextTimeoutMs <= 0) {
    throw new Error(`Invalid --timeout value: ${String(rawArgs.timeout)}`);
  }

  let nextWorkspaceUrl;
  try {
    nextWorkspaceUrl = normalizeWorkspaceUrl(nextWorkspaceUrlRaw);
  } catch {
    throw new Error(`Invalid VORMBYTE_VISUAL_WORKSPACE_URL value: ${nextWorkspaceUrlRaw}`);
  }

  return {
    host: nextHost,
    port: nextPort,
    token: nextToken,
    timeoutMs: nextTimeoutMs,
    workspaceUrl: nextWorkspaceUrl,
    hasExplicitPort,
  };
}

function normalizeWorkspaceUrl(value) {
  const trimmed = String(value ?? "").trim();
  if (!trimmed) return "";

  const url = new URL(trimmed);
  url.hash = "";

  const entries = Array.from(url.searchParams.entries()).sort(([leftName, leftValue], [rightName, rightValue]) => {
    const nameComparison = leftName.localeCompare(rightName);
    return nameComparison !== 0 ? nameComparison : leftValue.localeCompare(rightValue);
  });
  url.search = "";
  for (const [name, entryValue] of entries) {
    url.searchParams.append(name, entryValue);
  }

  return url.toString();
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function parseArgs(argv) {
  const parsed = {};
  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    if (!arg.startsWith("--")) continue;
    const equalsIndex = arg.indexOf("=");
    if (equalsIndex !== -1) {
      parsed[arg.slice(2, equalsIndex)] = arg.slice(equalsIndex + 1);
      continue;
    }
    const key = arg.slice(2);
    const next = argv[index + 1];
    if (!next || next.startsWith("--")) {
      parsed[key] = "true";
      continue;
    }
    parsed[key] = next;
    index += 1;
  }
  return parsed;
}

function workspaceParams(input) {
  const explicitParams = isPlainObject(input.params)
    ? input.params
    : isPlainObject(input.parameters)
      ? input.parameters
      : {};
  const flatParams = {};

  for (const key of ["pageId", "toolName", "args", "reason", "actionId", "timeoutMs"]) {
    if (input[key] !== undefined) {
      flatParams[key] = input[key];
    }
  }

  return { ...flatParams, ...explicitParams };
}

function isPlainObject(value) {
  return Boolean(value && typeof value === "object" && !Array.isArray(value));
}

async function createBridgeServer({ host, port, allowPortFallback }) {
  try {
    return await listenBridgeServer({ host, port });
  } catch (error) {
    if (!allowPortFallback || error?.code !== "EADDRINUSE") {
      throw error;
    }

    log(`Workspace bridge port ${port} is already in use; falling back to an ephemeral port.`);
    return listenBridgeServer({ host, port: 0 });
  }
}

async function listenBridgeServer({ host, port }) {
  const server = createHttpServer(handleHttpRequest);
  const wss = new WebSocketServer({
    server,
    verifyClient: (info, callback) => {
      if (!isAllowedOrigin(info.origin)) {
        callback(false, 403, "Forbidden");
        return;
      }

      const url = new URL(info.req.url ?? "/", `http://${info.req.headers.host ?? `${host}:${port}`}`);
      if ((url.searchParams.get("token") ?? "") !== token) {
        callback(false, 401, "Invalid token");
        return;
      }

      callback(true);
    },
  });

  return new Promise((resolve, reject) => {
    let settled = false;
    const cleanup = () => {
      server.off("listening", handleListening);
      server.off("error", handleError);
      wss.off("error", handleError);
    };
    const handleListening = () => {
      if (settled) return;
      settled = true;
      cleanup();
      resolve({ wss, httpServer: server });
    };
    const handleError = (error) => {
      if (settled) return;
      settled = true;
      cleanup();
      wss.close();
      try {
        server.close();
      } catch {
        // The HTTP bridge may fail before it starts listening.
      }
      reject(error);
    };

    server.once("listening", handleListening);
    server.once("error", handleError);
    wss.once("error", handleError);
    server.listen(port, host);
  });
}

async function createControlServer(socketPath) {
  if (!isWindowsPipePath(socketPath)) {
    await mkdir(dirname(socketPath), { recursive: true, mode: 0o700 });
  }
  await cleanupStaleControlSocket(socketPath);

  const server = createIpcServer((socket) => acceptAgentConnection(socket));

  return new Promise((resolve, reject) => {
    let settled = false;
    const cleanup = () => {
      server.off("listening", handleListening);
      server.off("error", handleError);
    };
    const handleListening = () => {
      if (settled) return;
      settled = true;
      cleanup();
      resolve(server);
    };
    const handleError = (error) => {
      if (settled) return;
      settled = true;
      cleanup();
      try {
        server.close();
      } catch {
        // The control server may fail before it starts listening.
      }
      reject(error);
    };

    server.once("listening", handleListening);
    server.once("error", handleError);
    server.listen(socketPath);
  });
}

async function cleanupStaleControlSocket(socketPath) {
  try {
    const socket = await connectControlSocketPath(socketPath, 200);
    socket.end();
    const error = new Error("A Vormbyte Visual daemon is already running for this site.");
    error.code = "EADDRINUSE";
    throw error;
  } catch (error) {
    if (error?.code === "ENOENT") return;

    if (error?.code === "ECONNREFUSED" || error?.code === "ENOTSOCK") {
      await unlinkControlSocketPath(socketPath);
      return;
    }

    throw error;
  }
}

function connectControlSocketPath(socketPath, timeoutMs) {
  return new Promise((resolve, reject) => {
    const socket = createConnection(socketPath);
    let settled = false;
    const timeout = setTimeout(() => {
      const error = new Error(`Timed out connecting to Vormbyte Visual daemon socket: ${socketPath}`);
      error.code = "ETIMEDOUT";
      cleanup();
      socket.destroy();
      reject(error);
    }, timeoutMs);

    const cleanup = () => {
      clearTimeout(timeout);
      socket.off("connect", handleConnect);
      socket.off("error", handleError);
      socket.off("close", handleClose);
    };
    const handleConnect = () => {
      if (settled) return;
      settled = true;
      cleanup();
      resolve(socket);
    };
    const handleError = (error) => {
      if (settled) return;
      settled = true;
      cleanup();
      reject(error);
    };
    const handleClose = () => {
      if (settled) return;
      settled = true;
      cleanup();
      reject(new Error("Vormbyte Visual daemon closed the control socket before it connected."));
    };

    socket.once("connect", handleConnect);
    socket.once("error", handleError);
    socket.once("close", handleClose);
  });
}

async function readState(workspaceUrlForSite = "") {
  let normalizedWorkspaceUrl;
  try {
    normalizedWorkspaceUrl = normalizeWorkspaceUrl(workspaceUrlForSite);
  } catch {
    normalizedWorkspaceUrl = "";
  }

  if (!normalizedWorkspaceUrl) {
    return {};
  }

  try {
    const parsed = JSON.parse(await readFile(stateFilePath(normalizedWorkspaceUrl), "utf8"));
    if (!stateMatchesSite(parsed, normalizedWorkspaceUrl)) {
      return {};
    }

    const cachedPort = Number(parsed.port);
    return {
      port: Number.isInteger(cachedPort) && cachedPort > 0 && cachedPort <= 65535 ? cachedPort : undefined,
      token: typeof parsed.token === "string" && parsed.token ? parsed.token : undefined,
      workspaceUrl: typeof parsed.workspaceUrl === "string" && parsed.workspaceUrl ? parsed.workspaceUrl : undefined,
    };
  } catch {
    return {};
  }
}

async function writeState(nextState, workspaceUrlForSite = "") {
  if (!workspaceUrlForSite) return;

  const filePath = stateFilePath(workspaceUrlForSite);
  await mkdir(dirname(filePath), { recursive: true, mode: 0o700 });
  await writeFile(filePath, `${JSON.stringify(nextState, null, 2)}\n`, { mode: 0o600 });
}

function handleHttpRequest(request, response) {
  if (request.url !== "/bridge-token") {
    response.writeHead(404);
    response.end();
    return;
  }

  const origin = request.headers.origin;
  if (!isAllowedOrigin(origin)) {
    response.writeHead(403);
    response.end("Forbidden");
    return;
  }

  if (origin) {
    response.setHeader("Access-Control-Allow-Origin", origin);
    response.setHeader("Vary", "Origin");
  }
  response.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");

  if (request.method === "OPTIONS") {
    response.writeHead(204);
    response.end();
    return;
  }

  if (request.method !== "GET") {
    response.writeHead(405);
    response.end("Method Not Allowed");
    return;
  }

  response.writeHead(200, { "Content-Type": "application/json" });
  response.end(JSON.stringify({ token }));
}

function isAllowedOrigin(origin) {
  if (!origin) return true;
  if (!workspaceUrl) return false;

  try {
    return new URL(origin).origin === new URL(workspaceUrl).origin;
  } catch {
    return false;
  }
}

function closeServer(server) {
  return new Promise((resolve) => {
    try {
      server.close(() => resolve());
    } catch {
      resolve();
    }
  });
}

async function closeControlServer() {
  if (!controlServer) return;

  const server = controlServer;
  controlServer = null;
  await closeServer(server).catch(() => {});

  if (controlSocketPath) {
    await unlinkControlSocketPath(controlSocketPath).catch((error) => {
      log(`Failed to remove control socket ${controlSocketPath}: ${error.message}`);
    });
  }
}

function daemonSocketPath(workspaceUrlForSite = "") {
  if (process.platform === "win32") {
    return `\\\\.\\pipe\\vormbyte-visual-${daemonSiteKey(workspaceUrlForSite)}`;
  }

  return daemonRuntimePath(workspaceUrlForSite, "sock");
}

function startLockFilePath(workspaceUrlForSite = "") {
  return daemonRuntimePath(workspaceUrlForSite, "lock");
}

function stateFilePath(workspaceUrlForSite = "") {
  return daemonRuntimePath(workspaceUrlForSite, "json");
}

function daemonRuntimePath(workspaceUrlForSite, extension) {
  const key = daemonSiteKey(workspaceUrlForSite);
  let directory = join(stateBaseDirectory(), "vormbyte-visual");
  let path = join(directory, `${key}.${extension}`);

  if (Buffer.byteLength(path) > DAEMON_SOCKET_PATH_MAX_BYTES) {
    directory = join(tmpdir(), `vormbyte-visual-${userId()}`);
    path = join(directory, `${key}.${extension}`);
  }

  return path;
}

function stateBaseDirectory() {
  if (process.platform === "win32") {
    return process.env.LOCALAPPDATA || join(homedir(), "AppData", "Local");
  }

  if (process.platform === "darwin") {
    return join(homedir(), "Library", "Application Support");
  }

  return join(homedir(), ".local", "state");
}

function isWindowsPipePath(path) {
  return process.platform === "win32" && path.startsWith("\\\\.\\pipe\\");
}

async function unlinkControlSocketPath(socketPath) {
  if (isWindowsPipePath(socketPath)) return;

  await unlink(socketPath).catch((error) => {
    if (error?.code !== "ENOENT") {
      throw error;
    }
  });
}

function daemonSiteKey(url) {
  const keySource = url ? daemonSiteUrl(url) : "unconfigured";
  return createHash("sha256").update(keySource).digest("hex").slice(0, 24);
}

function daemonSiteUrl(url) {
  const parsed = new URL(normalizeWorkspaceUrl(url));
  parsed.hash = "";
  parsed.search = "";

  const adminIndex = parsed.pathname.indexOf("/wp-admin/");
  parsed.pathname = adminIndex === -1 ? "/" : parsed.pathname.slice(0, adminIndex + 1);

  return parsed.toString();
}

function stateMatchesSite(state, normalizedWorkspaceUrl) {
  if (!state?.workspaceUrl) return false;

  try {
    return daemonSiteUrl(state.workspaceUrl) === daemonSiteUrl(normalizedWorkspaceUrl);
  } catch {
    return false;
  }
}

function userId() {
  return typeof process.getuid === "function" ? String(process.getuid()) : "user";
}

function textResult(value) {
  return {
    content: [
      {
        type: "text",
        text: JSON.stringify(value, null, 2),
      },
    ],
  };
}

function bridgeStatus() {
  return {
    packageName: BRIDGE_PACKAGE_NAME,
    packageVersion: BRIDGE_PACKAGE_VERSION,
    protocol: {
      name: WORKSPACE_PROTOCOL_NAME,
      version: WORKSPACE_PROTOCOL_VERSION,
      requiredCapabilities: [...REQUIRED_WORKSPACE_PROTOCOL_CAPABILITIES],
    },
  };
}

async function ensureWorkspaceCompatible() {
  if (workspaceProtocolValidated) return;

  const workspace = await workspaceCall("workspace_status", {}, { skipCompatibilityCheck: true });
  const compatibility = workspaceCompatibility(workspace);
  if (!compatibility.ok) {
    throw new Error(compatibility.error);
  }

  workspaceProtocolValidated = true;
}

function workspaceCompatibility(workspace) {
  const error = workspaceProtocolError(workspace);
  if (error) {
    return {
      ok: false,
      error,
      expected: bridgeStatus().protocol,
      actual: isPlainObject(workspace?.protocol) ? workspace.protocol : null,
    };
  }

  return { ok: true };
}

function workspaceProtocolError(workspace) {
  const protocol = isPlainObject(workspace?.protocol) ? workspace.protocol : null;
  if (!protocol) {
    return (
      `The connected Vormbyte Visual dashboard does not report ${WORKSPACE_PROTOCOL_NAME} protocol metadata. ` +
      `This MCP bridge requires protocol v${WORKSPACE_PROTOCOL_VERSION}. ` +
      `Use the dashboard's compatible MCP package (${BRIDGE_PACKAGE_NAME}@${BRIDGE_PACKAGE_VERSION}) and restart the MCP server.`
    );
  }

  const protocolName = typeof protocol.name === "string" && protocol.name ? protocol.name : "(missing)";
  const protocolVersion = Number(protocol.version);
  const compatiblePackage = workspaceCompatibleMcpPackage(protocol);

  if (protocolName !== WORKSPACE_PROTOCOL_NAME || protocolVersion !== WORKSPACE_PROTOCOL_VERSION) {
    return (
      `Vormbyte Visual protocol mismatch. This MCP bridge supports ${WORKSPACE_PROTOCOL_NAME} ` +
      `v${WORKSPACE_PROTOCOL_VERSION}, but the connected dashboard reports ${protocolName} ` +
      `v${Number.isFinite(protocolVersion) ? protocolVersion : "(missing)"}. ` +
      `Use the dashboard's compatible MCP package (${compatiblePackage}) and restart the MCP server.`
    );
  }

  const capabilities = Array.isArray(protocol.capabilities)
    ? protocol.capabilities.filter((capability) => typeof capability === "string")
    : [];
  const missingCapabilities = REQUIRED_WORKSPACE_PROTOCOL_CAPABILITIES.filter(
    (capability) => !capabilities.includes(capability)
  );

  if (missingCapabilities.length > 0) {
    return (
      `Vormbyte Visual protocol mismatch. The connected dashboard is missing required ` +
      `capabilities: ${missingCapabilities.join(", ")}. Use the dashboard's compatible MCP ` +
      `package (${compatiblePackage}) and restart the MCP server.`
    );
  }

  return null;
}

function workspaceCompatibleMcpPackage(protocol) {
  return typeof protocol.compatibleMcpPackage === "string" && protocol.compatibleMcpPackage
    ? protocol.compatibleMcpPackage
    : `${BRIDGE_PACKAGE_NAME}@${BRIDGE_PACKAGE_VERSION}`;
}

async function workspaceCall(method, params, options = {}) {
  if (!workspaceSocket || workspaceSocket.readyState !== WebSocket.OPEN) {
    throw new Error(
      "Vormbyte Visual is not connected. Do not retry page tools yet. Ask the user to connect it first:\n\n" +
        connectionInstructions()
    );
  }

  if (!options.skipCompatibilityCheck && method !== "workspace_status") {
    await ensureWorkspaceCompatible();
  }

  const id = workspaceRequestSeq++;
  const payload = {
    type: "request",
    id,
    method,
    params: params ?? {},
  };

  const promise = new Promise((resolve, reject) => {
    const requestTimeoutMs = workspaceRequestTimeoutMs(method, params);
    const timeout = setTimeout(() => {
      workspacePending.delete(id);
      reject(new Error(`Workspace request timed out after ${requestTimeoutMs}ms: ${method}`));
    }, requestTimeoutMs);

    workspacePending.set(id, { resolve, reject, timeout });
  });

  workspaceSocket.send(JSON.stringify(payload));
  return promise;
}

function workspaceRequestTimeoutMs(method, params) {
  if (method !== "workspace_wait_action") return timeoutMs;

  const waitMs = Number(params?.timeoutMs ?? 60000);
  if (!Number.isFinite(waitMs)) return Math.max(timeoutMs, 65000);

  return Math.max(timeoutMs, Math.min(Math.max(0, Math.floor(waitMs)) + 5000, 125000));
}

async function handleWorkspaceMessage(data) {
  const message = JSON.parse(String(data));
  if (message.type === "event") {
    log(`Workspace event: ${message.name}`);
    broadcastAgentEvent(message.name, message.payload ?? null);
    return;
  }

  if (message.type !== "response") {
    return;
  }

  const entry = workspacePending.get(message.id);
  if (!entry) return;

  workspacePending.delete(message.id);
  clearTimeout(entry.timeout);

  if (message.ok) {
    entry.resolve(message.result ?? null);
  } else {
    entry.reject(new Error(String(message.error ?? "Workspace request failed.")));
  }
}

function rejectAllWorkspacePending(reason) {
  for (const [id, entry] of workspacePending.entries()) {
    workspacePending.delete(id);
    clearTimeout(entry.timeout);
    entry.reject(new Error(reason));
  }
}

function log(message) {
  process.stderr.write(`[vormbyte-visual] ${message}\n`);
}

function announce(message) {
  process.stderr.write(`[vormbyte-visual] ${message}\n`);
}

function connectionAnnouncement() {
  if (!workspaceUrl) {
    return "Set VORMBYTE_VISUAL_WORKSPACE_URL to your Vormbyte Visual URL, then restart this MCP server.";
  }

  return `Vormbyte Visual connection code: ${workspaceConnectionCode()}\nDirect connection URL: ${workspaceConnectionUrl()}`;
}

function connectionInstructions() {
  if (workspaceUrl) {
    return [
      "Tell the user exactly this:",
      "",
      "Open Vormbyte Visual from the WordPress menu if it is not already open.",
      "",
      `Paste this connection code into Vormbyte Visual: ${workspaceConnectionCode()}`,
      "",
      "If you prefer a direct link, open this URL:",
      "",
      workspaceConnectionUrl(),
      "",
      "After the dashboard shows Connected, tell me and I can open pages and edit them.",
    ].join("\n");
  }

  return [
    "Tell the user exactly this:",
    "",
    "Set VORMBYTE_VISUAL_WORKSPACE_URL in the MCP server config to your Vormbyte Visual URL, then restart the MCP server.",
    "",
    "You can copy a setup prompt from the Vormbyte Visual page.",
  ].join("\n");
}

function workspaceConnectionCode() {
  return `visual:${actualPort}`;
}

function workspaceConnectionUrl() {
  const url = new URL(workspaceUrl);
  url.searchParams.set("mcp", "yes");
  url.searchParams.set("mcp-port", String(actualPort));
  return url.toString();
}
