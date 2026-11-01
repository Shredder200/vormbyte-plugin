export interface AgentToolResult<TDetails = unknown> {
	content: Array<{ type: "text"; text: string }>;
	details?: TDetails;
	isError?: boolean;
}

export interface AgentTool {
	name: string;
	label?: string;
	description?: string;
	parameters?: unknown;
	execute: (
		toolCallId: string,
		params: any,
	) => AgentToolResult | Promise<AgentToolResult>;
}
