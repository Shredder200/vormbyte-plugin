import {
	AUTO_RECONNECT_INTERVAL_MS,
	BRIDGE_PORTS_KEY,
	LAST_BRIDGE_PORT_KEY,
	MAX_SAVED_BRIDGE_PORTS,
	workspaceData,
} from "./workspace-config";
import type { BridgeConnection, BridgeRequest, BridgeResponse } from "./workspace-types";

interface WorkspaceTransportHost {
	dispatch: (method: string, params: unknown) => Promise<unknown>;
	getState: () => unknown;
	log: (message: string) => void;
	logError: (err: unknown) => void;
	setBridgeStatus: (text: string, connected: boolean) => void;
	showConnectionDetail: (message: string) => void;
	updateConnectionPanel: () => void;
	onBridgeConnected: (auto: boolean) => void;
	failPendingConfirmations: (message: string) => void;
}

export class WorkspaceTransport {
	private bridgeConnections = new Map<number, BridgeConnection>();
	private reconnectTimers = new Map<number, number>();

	constructor(private host: WorkspaceTransportHost) {}

	async connectFromMcpPort(port: number, auto = false): Promise<void> {
		if (!auto) {
			const hasConnectedBridge = this.getConnectedBridgeCount() > 0;
			this.host.setBridgeStatus(hasConnectedBridge ? "Adding agent" : "Fetching token", hasConnectedBridge);
			this.host.showConnectionDetail(`Connecting to local agent on port ${port}...`);
		}

		const response = await fetch(`http://127.0.0.1:${port}/bridge-token`);
		if (!response.ok) {
			throw new Error(`Failed to fetch local MCP token: HTTP ${response.status}`);
		}

		const payload = (await response.json()) as { token?: unknown };
		if (typeof payload.token !== "string" || !payload.token) {
			throw new Error("Local MCP bridge did not return a token.");
		}

		const bridgeUrl = new URL(`ws://127.0.0.1:${port}`);
		bridgeUrl.searchParams.set("token", payload.token);
		this.connectToBridgeUrl(bridgeUrl, port, auto);
	}

	startAutoReconnect(port: number, immediate = false): void {
		if (this.reconnectTimers.has(port)) {
			return;
		}

		if (this.getConnectedBridgeCount() === 0) {
			this.host.setBridgeStatus(this.reconnectTimers.size === 0 ? "Waiting for agent" : "Waiting for agents", false);
		} else {
			this.updateBridgeStatus();
		}
		const attempt = (): void => {
			if (this.hasBridgeOnPort(port)) {
				return;
			}

			this.connectFromMcpPort(port, true).catch(() => {
				this.updateBridgeStatus();
				this.updateWaitingConnectionDetail();
			});
		};

		this.reconnectTimers.set(port, window.setInterval(attempt, AUTO_RECONNECT_INTERVAL_MS));
		this.updateWaitingConnectionDetail();
		if (immediate) {
			attempt();
		}
	}

	stopAutoReconnect(port?: number): void {
		if (port !== undefined) {
			const timer = this.reconnectTimers.get(port);
			if (timer !== undefined) {
				window.clearInterval(timer);
				this.reconnectTimers.delete(port);
			}
			return;
		}

		for (const timer of this.reconnectTimers.values()) {
			window.clearInterval(timer);
		}
		this.reconnectTimers.clear();
	}

	disconnect(): void {
		this.stopAutoReconnect();
		this.host.failPendingConfirmations("Workspace bridge disconnected before the action was confirmed.");
		for (const connection of this.bridgeConnections.values()) {
			connection.socket.close();
		}
		this.bridgeConnections.clear();
		this.updateBridgeStatus();
	}

	disconnectBridge(port: number): void {
		const connection = this.bridgeConnections.get(port);
		if (!connection) return;

		this.bridgeConnections.delete(port);
		connection.socket.close();
		this.updateBridgeStatus();
	}

	updateBridgeStatus(): void {
		const count = this.getConnectedBridgeCount();
		const label =
			count === 0
				? this.reconnectTimers.size === 0
					? "Disconnected"
					: this.reconnectTimers.size === 1
						? "Waiting for agent"
						: "Waiting for agents"
				: count === 1
					? "Connected"
					: `${count} connected`;
		this.host.setBridgeStatus(label, count > 0);
	}

	getConnectedBridgeCount(): number {
		let count = 0;
		for (const connection of this.bridgeConnections.values()) {
			if (connection.socket.readyState === WebSocket.OPEN) count++;
		}
		return count;
	}

	connectedBridgePorts(): number[] {
		return Array.from(this.bridgeConnections.values())
			.filter((connection) => connection.socket.readyState === WebSocket.OPEN)
			.map((connection) => connection.port)
			.sort((a, b) => a - b);
	}

	retryingBridgePorts(): number[] {
		return Array.from(this.reconnectTimers.keys()).sort((a, b) => a - b);
	}

	sendEvent(name: string, payload: unknown): void {
		for (const connection of this.bridgeConnections.values()) {
			this.sendEventTo(connection.socket, name, payload);
		}
	}

	sendEventTo(socket: WebSocket, name: string, payload: unknown): void {
		if (socket.readyState !== WebSocket.OPEN) return;
		socket.send(JSON.stringify({ type: "event", name, payload }));
	}

	parseConnectionCode(raw: string): number {
		const trimmed = raw.trim();
		if (!trimmed) {
			throw new Error("Paste a connection code like visual:51234.");
		}

		let candidate: string | null;
		try {
			const url = new URL(trimmed, workspaceData.workspaceUrl);
			candidate = url.searchParams.get("mcp-port") ?? url.searchParams.get("port");
		} catch {
			candidate = null;
		}

		if (!candidate) {
			candidate = trimmed.match(/\bvisual:(\d{1,5})\b/i)?.[1] ?? (trimmed.match(/^\d{1,5}$/)?.[0] ?? null);
		}

		if (!candidate) {
			throw new Error("Paste a connection code like visual:51234.");
		}

		const port = Number(candidate);
		if (!Number.isInteger(port) || port <= 0 || port > 65535) {
			throw new Error("Connection code has an invalid port.");
		}

		return port;
	}

	readSavedBridgePorts(): number[] {
		try {
			const value = window.localStorage.getItem(BRIDGE_PORTS_KEY);
			if (value) {
				const parsed = JSON.parse(value) as unknown;
				if (Array.isArray(parsed)) {
					return this.normalizeBridgePorts(parsed);
				}
			}

			const legacyValue = window.localStorage.getItem(LAST_BRIDGE_PORT_KEY);
			if (!legacyValue) return [];
			return [this.parseConnectionCode(legacyValue)];
		} catch {
			return [];
		}
	}

	getMcpPortFromUrl(): number | null {
		const params = new URLSearchParams(window.location.search);
		if (params.get("mcp") !== "yes") return null;

		const port = Number(params.get("mcp-port"));
		if (!Number.isInteger(port) || port <= 0 || port > 65535) {
			this.host.log("Ignoring invalid mcp-port URL parameter.");
			return null;
		}

		return port;
	}

	private connectToBridgeUrl(bridgeUrl: URL, port: number, auto = false): void {
		this.disconnectBridge(port);

		bridgeUrl.searchParams.set("role", "workspace");

		if (!auto) {
			const hasConnectedBridge = this.getConnectedBridgeCount() > 0;
			this.host.setBridgeStatus(hasConnectedBridge ? "Adding agent" : "Connecting", hasConnectedBridge);
		}
		const socket = new WebSocket(bridgeUrl.toString());
		this.bridgeConnections.set(port, { port, socket });

		socket.addEventListener("open", () => {
			this.saveBridgePort(port);
			this.stopAutoReconnect(port);
			this.host.onBridgeConnected(auto);
			this.updateBridgeStatus();
			this.host.log(`Connected to local MCP bridge on visual:${port}.`);
			this.sendEventTo(socket, "workspace_state", this.host.getState());
		});

		socket.addEventListener("message", (event) => {
			this.handleBridgeMessage(socket, event.data).catch((err) => this.host.logError(err));
		});

		socket.addEventListener("close", () => {
			const connection = this.bridgeConnections.get(port);
			if (connection?.socket === socket) {
				this.bridgeConnections.delete(port);
				if (this.getConnectedBridgeCount() === 0) {
					this.host.failPendingConfirmations("Workspace bridge disconnected before the action was confirmed.");
				}
				if (!this.reconnectTimers.has(port)) {
					this.updateBridgeStatus();
				}
				this.host.log(`Bridge visual:${port} disconnected.`);
				if (this.readSavedBridgePorts().includes(port)) {
					this.startAutoReconnect(port);
				}
			}
		});

		socket.addEventListener("error", () => {
			if (!auto) {
				this.host.setBridgeStatus("Connection failed", false);
				this.host.showConnectionDetail(`Could not connect to local agent on port ${port}.`);
			}
		});
	}

	private async handleBridgeMessage(socket: WebSocket, raw: unknown): Promise<void> {
		const request = JSON.parse(String(raw)) as BridgeRequest;
		if (request.type !== "request") return;

		try {
			const result = await this.host.dispatch(request.method, request.params);
			this.sendResponse(socket, { type: "response", id: request.id, ok: true, result });
		} catch (err) {
			this.sendResponse(socket, {
				type: "response",
				id: request.id,
				ok: false,
				error: err instanceof Error ? err.message : String(err),
			});
		}
	}

	private sendResponse(socket: WebSocket, response: BridgeResponse): void {
		if (socket.readyState !== WebSocket.OPEN) return;
		socket.send(JSON.stringify(response));
	}

	private saveBridgePort(port: number): void {
		try {
			const ports = this.normalizeBridgePorts([port, ...this.readSavedBridgePorts()]);
			window.localStorage.setItem(BRIDGE_PORTS_KEY, JSON.stringify(ports));
			window.localStorage.setItem(LAST_BRIDGE_PORT_KEY, `visual:${ports[0]}`);
		} catch {
			// Losing this just means the user may need to paste the code again later.
		}
	}

	private normalizeBridgePorts(values: unknown[]): number[] {
		const ports: number[] = [];
		for (const value of values) {
			let port = typeof value === "number" ? value : NaN;
			if (typeof value === "string") {
				try {
					port = this.parseConnectionCode(value);
				} catch {
					port = Number(value);
				}
			}
			if (!Number.isInteger(port) || port <= 0 || port > 65535 || ports.includes(port)) continue;
			ports.push(port);
			if (ports.length >= MAX_SAVED_BRIDGE_PORTS) break;
		}
		return ports;
	}

	private hasBridgeOnPort(port: number): boolean {
		const connection = this.bridgeConnections.get(port);
		return Boolean(
			connection &&
				(connection.socket.readyState === WebSocket.CONNECTING || connection.socket.readyState === WebSocket.OPEN)
		);
	}

	private updateWaitingConnectionDetail(): void {
		const retryingPorts = this.retryingBridgePorts();
		const savedPorts = this.readSavedBridgePorts();
		const ports = retryingPorts.length > 0 ? retryingPorts : savedPorts;
		if (ports.length === 0) return;

		const codeList = ports.map((port) => `visual:${port}`).join(", ");
		this.host.showConnectionDetail(
			`Waiting for local agent${ports.length === 1 ? "" : "s"} on ${codeList}. If your agent gives you a new code, paste it here.`
		);
	}
}
