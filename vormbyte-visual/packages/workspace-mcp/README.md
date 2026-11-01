# Vormbyte Visual MCP

MCP server for connecting coding agents to Vormbyte Visual in WordPress.

## Usage

Add this server to your MCP client configuration:

```json
{
  "mcpServers": {
    "vormbyte-visual": {
      "type": "stdio",
      "command": "npx",
      "args": ["-y", "vormbyte-visual-mcp@latest"],
      "env": {
        "VORMBYTE_VISUAL_WORKSPACE_URL": "https://example.com/wp-admin/admin-post.php?action=vormbyte-visual"
      }
    }
  }
}
```

Open the Vormbyte Visual page in WordPress, then paste the connection code shown by the MCP server.

The first MCP client for a WordPress site starts a local daemon automatically. Later MCP clients configured with a
Vormbyte Visual URL on the same site reuse the same daemon through a per-site local control endpoint, so multiple agents
can connect to the same Vormbyte Visual workspace without changing the user's MCP configuration. Different WordPress
sites use different daemons. A daemon exits after its last MCP client disconnects.

On Linux the MCP control socket and per-site bridge state are created under `~/.local/state/vormbyte-visual/`; on macOS
they use `~/Library/Application Support/vormbyte-visual/`. On Windows the MCP control endpoint is a named pipe under
`\\.\pipe\`, while per-site bridge state is stored under `%LOCALAPPDATA%\vormbyte-visual\`. The browser dashboard still
connects through the local TCP bridge port shown as `visual:<port>`, because browsers cannot open local control
endpoints directly.

## Options

- `--workspace-url <url>` or `VORMBYTE_VISUAL_WORKSPACE_URL`: Vormbyte Visual URL.
- `--host <host>` or `VORMBYTE_VISUAL_WORKSPACE_HOST`: bridge host. Defaults to `127.0.0.1`.
- `--port <port>` or `VORMBYTE_VISUAL_WORKSPACE_PORT`: browser bridge port for this workspace URL. Defaults to the saved per-site bridge port, or an ephemeral port that is saved after startup.
- `--token <token>` or `VORMBYTE_VISUAL_WORKSPACE_TOKEN`: connection token. Defaults to a generated token.
- `--timeout <ms>` or `VORMBYTE_VISUAL_WORKSPACE_TIMEOUT`: workspace request timeout. Defaults to `30000`.
