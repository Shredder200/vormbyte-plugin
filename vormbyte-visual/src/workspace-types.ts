export type PageStatus = "loading" | "ready" | "error";
export type PageInteractionName = "click" | "fill" | "type" | "press" | "select" | "check" | "uncheck" | "scroll";
export type ConfirmableModelAction = "workspace_eval" | `backend_tool:${string}` | `page_tool:${string}` | `page_interaction:${PageInteractionName}`;
export type ConfirmationDecision = "allow_once" | "allow_session" | "allow_all_tools_session" | "deny";
export type WorkspaceActionStatus = "waiting_for_confirmation" | "running" | "succeeded" | "failed" | "denied";

export interface PageElementRef {
	ref: string;
	element: Element;
	url: string;
	role: string;
	name: string;
	selector: string;
}

export interface PageElementRefStore {
	createdAt: number;
	url: string;
	refs: Map<string, PageElementRef>;
}

export interface PageSnapshotItem {
	ref: string;
	role: string;
	tag: string;
	name: string;
	selector: string;
	depth: number;
	attributes: Record<string, string | boolean | number>;
}

export interface ConfirmationDetail {
	label: string;
	value: string;
	format?: "text" | "code";
}

export interface PendingConfirmation {
	id: number;
	actionId: string;
	action: ConfirmableModelAction;
	title: string;
	description: string;
	details: ConfirmationDetail[];
}

export interface WorkspaceAction {
	id: string;
	action: ConfirmableModelAction;
	title: string;
	status: WorkspaceActionStatus;
	createdAt: number;
	updatedAt: number;
	result?: unknown;
	error?: string;
	run: () => Promise<unknown>;
}

export interface WorkspacePage {
	id: string;
	title: string;
	url: string;
	status: PageStatus;
	openedAt: number;
	iframe: HTMLIFrameElement;
}

export interface BackendToolDefinition {
	name: string;
	label?: string;
	description?: string;
	category?: string;
	annotations?: Record<string, unknown>;
	dangerous?: boolean;
	requiresConfirmation?: boolean;
}

export interface PageToolDefinition {
	name: string;
	label?: string;
	description?: string;
	parameters?: unknown;
}

export interface BridgeRequest {
	type: "request";
	id: string | number;
	method: string;
	params?: unknown;
}

export interface BridgeResponse {
	type: "response";
	id: string | number;
	ok: boolean;
	result?: unknown;
	error?: string;
}

export interface BridgeConnection {
	port: number;
	socket: WebSocket;
}

export interface WorkspaceProtocolInfo {
	name: string;
	version: number;
	capabilities: string[];
	compatibleMcpPackage: string;
	visualVersion: string;
}

export interface WorkspaceState {
	connected: boolean;
	protocol: WorkspaceProtocolInfo;
	activePageId: string | null;
	requestGuide: string;
	activePageToolCallGuide?: string | null;
	activePageSuggestedNextCall?: unknown;
	methods: string[];
	pages: Array<{
		id: string;
		title: string;
		url: string;
		status: PageStatus;
		openedAt: number;
		tools: string[];
	}>;
}

export interface StoredWorkspaceTabs {
	activePageId: string | null;
	pages: Array<{
		id: string;
		title: string;
		url: string;
		openedAt: number;
	}>;
}
