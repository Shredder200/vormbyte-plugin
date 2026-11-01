import { workspaceData } from "./workspace-config";
import type { BackendToolDefinition, ConfirmableModelAction, PendingConfirmation } from "./workspace-types";
import { cloneSerializable } from "./workspace-utils";

interface WorkspaceBackendToolHost {
	log: (message: string) => void;
	confirmToolAction: (
		action: ConfirmableModelAction,
		request: Pick<PendingConfirmation, "title" | "description" | "details"> & { reason: string },
		run: () => Promise<unknown>,
	) => unknown;
	isToolAllowed: (action: ConfirmableModelAction) => boolean;
}

export class WorkspaceBackendTools {
	private backendToolCache: Map<string, BackendToolDefinition> | null = null;

	constructor(private host: WorkspaceBackendToolHost) {}

	async discoverBackendTools(params: Record<string, unknown>): Promise<unknown> {
		const result = await this.backendAjax("vormbyte_visual_safe_backend_tools_discover", params);
		const tools = this.backendToolsFromResult(result);
		if (tools && this.isFullBackendToolDiscovery(params)) {
			this.backendToolCache = new Map(tools.map((tool) => [tool.name, tool]));
		}

		return cloneSerializable(result);
	}

	async callBackendTool(
		name: string,
		args: Record<string, unknown>,
		reason: string,
		agentAction = false,
	): Promise<unknown> {
		if (agentAction) this.host.log(`Calling backend tool ${name}.`);
		const tool = await this.getBackendToolDefinition(name);
		const action = `backend_tool:${name}` as ConfirmableModelAction;
		const run = () => this.runBackendTool(name, args);
		if (tool.requiresConfirmation === true && !this.host.isToolAllowed(action)) {
			return this.host.confirmToolAction(action, {
				title: `Review backend tool: ${name}`,
				description: "The agent wants to run a WordPress backend MCP tool that can change site data. Review the tool and arguments before allowing it.",
				reason,
				details: [
					{ label: "Tool", value: tool.label ? `${tool.label} (${name})` : name },
					{ label: "Arguments", value: JSON.stringify(args, null, 2), format: "code" },
				],
			}, run);
		}

		return run();
	}

	private async runBackendTool(name: string, args: Record<string, unknown>): Promise<unknown> {
		const result = await this.backendAjax("vormbyte_visual_safe_backend_tools_call", { name, args });
		return cloneSerializable(result);
	}

	private async getBackendToolDefinition(name: string): Promise<BackendToolDefinition> {
		if (!this.backendToolCache) {
			const result = await this.backendAjax("vormbyte_visual_safe_backend_tools_discover", { include_schemas: false });
			const tools = this.backendToolsFromResult(result) ?? [];
			this.backendToolCache = new Map(tools.map((tool) => [tool.name, tool]));
		}

		const tool = this.backendToolCache.get(name);
		if (!tool) {
			const result = await this.backendAjax("vormbyte_visual_safe_backend_tools_discover", { include_schemas: false });
			const tools = this.backendToolsFromResult(result) ?? [];
			this.backendToolCache = new Map(tools.map((item) => [item.name, item]));
			const refreshedTool = this.backendToolCache.get(name);
			if (refreshedTool) return refreshedTool;
		}

		const refreshedTool = this.backendToolCache.get(name);
		if (!refreshedTool) {
			throw new Error(`Backend tool is not available through the Visual-safe backend tools contract: ${name}`);
		}

		return refreshedTool;
	}

	private isFullBackendToolDiscovery(params: Record<string, unknown>): boolean {
		return !params.search && !params.category && params.include_dangerous !== false;
	}

	private backendToolsFromResult(result: unknown): BackendToolDefinition[] | null {
		const tools = result && typeof result === "object" && Array.isArray((result as { tools?: unknown }).tools)
			? (result as { tools: unknown[] }).tools
			: null;
		if (!tools) return null;

		return tools
			.filter((tool): tool is Record<string, unknown> => Boolean(tool && typeof tool === "object" && !Array.isArray(tool)))
			.map((tool) => ({
				name: typeof tool.name === "string" ? tool.name : "",
				label: typeof tool.label === "string" ? tool.label : undefined,
				description: typeof tool.description === "string" ? tool.description : undefined,
				category: typeof tool.category === "string" ? tool.category : undefined,
				annotations: tool.annotations && typeof tool.annotations === "object" && !Array.isArray(tool.annotations)
					? tool.annotations as Record<string, unknown>
					: undefined,
				dangerous: tool.dangerous === true,
				requiresConfirmation: tool.requires_confirmation === true,
			}))
			.filter((tool) => tool.name !== "");
	}

	private async backendAjax(action: string, body: Record<string, unknown>): Promise<unknown> {
		const url = new URL("admin-ajax.php", workspaceData.adminUrl);
		url.searchParams.set("action", action);
		url.searchParams.set("nonce", workspaceData.nonce);
		const response = await window.fetch(url, {
			method: "POST",
			credentials: "same-origin",
			headers: {
				"Content-Type": "application/json",
				"X-WP-Nonce": workspaceData.nonce,
			},
			body: JSON.stringify(body),
		});
		const data = await response.json().catch(() => null) as unknown;
		const payload = data && typeof data === "object" ? data as { success?: unknown; data?: unknown } : null;
		if (!response.ok || payload?.success === false) {
			const detail = payload?.data && typeof payload.data === "object" ? payload.data as { message?: unknown } : null;
			const message = typeof detail?.message === "string"
				? detail.message
				: `Backend tool request failed with HTTP ${response.status}.`;
			throw new Error(message);
		}

		return payload && "data" in payload ? payload.data : data;
	}
}
