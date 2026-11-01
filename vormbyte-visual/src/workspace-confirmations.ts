import type {
	ConfirmableModelAction,
	ConfirmationDecision,
	PendingConfirmation,
	WorkspaceAction,
	WorkspaceActionStatus,
} from "./workspace-types";
import { cloneSerializable, decodeJsonEscaped } from "./workspace-utils";

interface WorkspaceConfirmationHost {
	log: (message: string) => void;
	setAllToolsAllowed: (allowed: boolean) => void;
}

export class WorkspaceConfirmations {
	private nextActionId = 1;
	private nextConfirmationId = 1;
	private actions = new Map<string, WorkspaceAction>();
	private actionWaiters = new Map<string, Set<() => void>>();
	private pendingConfirmations = new Map<number, PendingConfirmation>();
	private sessionAllowedActions = new Set<ConfirmableModelAction>();
	private sessionAllowedAllTools = false;

	constructor(
		private confirmationsEl: HTMLDivElement,
		private host: WorkspaceConfirmationHost,
	) {}

	isActionAllowed(action: ConfirmableModelAction): boolean {
		return this.sessionAllowedActions.has(action) || this.isAllToolsAllowed(action);
	}

	isToolAllowed(action: ConfirmableModelAction): boolean {
		return this.isActionAllowed(action);
	}

	disableAllToolsAllowance(): void {
		if (!this.sessionAllowedAllTools) return;
		this.sessionAllowedAllTools = false;
		this.host.setAllToolsAllowed(false);
		this.host.log("Disabled allow-all mode for this workspace.");
	}

	enqueueToolConfirmation(
		action: ConfirmableModelAction,
		request: Pick<PendingConfirmation, "title" | "description" | "details"> & { reason: string },
		run: () => Promise<unknown>
	): unknown {
		if (this.isToolAllowed(action)) return run();

		const details = [...request.details];
		const insertAt = details.length > 0 ? 1 : 0;
		details.splice(insertAt, 0, { label: "Reason", value: decodeJsonEscaped(request.reason) });

		return this.enqueueConfirmableAction(action, {
			title: request.title,
			description: request.description,
			details,
		}, run);
	}

	enqueueConfirmableAction(
		action: ConfirmableModelAction,
		request: Pick<PendingConfirmation, "title" | "description" | "details">,
		run: () => Promise<unknown>
	): unknown {
		if (this.isActionAllowed(action)) return run();

		const actionId = `action-${this.nextActionId++}`;
		const now = Date.now();
		const workspaceAction: WorkspaceAction = {
			id: actionId,
			action,
			title: request.title,
			status: "waiting_for_confirmation",
			createdAt: now,
			updatedAt: now,
			run,
		};
		this.actions.set(actionId, workspaceAction);

		const id = this.nextConfirmationId++;
		this.pendingConfirmations.set(id, {
			id,
			actionId,
			action,
			...request,
		});
		this.renderConfirmations();
		this.host.log(`Waiting for user approval: ${request.title} (${actionId}).`);

		return this.serializeAction(workspaceAction);
	}

	failPendingConfirmations(message: string): void {
		if (this.pendingConfirmations.size === 0) return;
		for (const confirmation of this.pendingConfirmations.values()) {
			this.setActionError(confirmation.actionId, "failed", message);
		}
		this.pendingConfirmations.clear();
		this.renderConfirmations();
	}

	getActionStatus(actionId: string): unknown {
		return this.serializeAction(this.getAction(actionId));
	}

	async waitForAction(actionId: string, timeoutMs: number): Promise<unknown> {
		const action = this.getAction(actionId);
		if (this.isTerminalActionStatus(action.status) || timeoutMs === 0) {
			return this.serializeAction(action);
		}

		return new Promise((resolve) => {
			let timer: number | null = null;
			const finish = (): void => {
				if (timer !== null) {
					window.clearTimeout(timer);
					timer = null;
				}
				const waiters = this.actionWaiters.get(actionId);
				if (waiters) {
					waiters.delete(check);
					if (waiters.size === 0) this.actionWaiters.delete(actionId);
				}
				resolve(this.serializeAction(this.getAction(actionId)));
			};

			const check = (): void => {
				if (this.isTerminalActionStatus(this.getAction(actionId).status)) {
					finish();
				}
			};

			let waiters = this.actionWaiters.get(actionId);
			if (!waiters) {
				waiters = new Set();
				this.actionWaiters.set(actionId, waiters);
			}
			waiters.add(check);
			timer = window.setTimeout(finish, timeoutMs);
		});
	}

	private renderConfirmations(): void {
		this.confirmationsEl.innerHTML = "";
		const confirmations = Array.from(this.pendingConfirmations.values());
		this.confirmationsEl.hidden = confirmations.length === 0;

		for (const confirmation of confirmations) {
			const card = document.createElement("section");
			card.className = "vormbyte-visual-confirmation-card";
			card.setAttribute("aria-label", confirmation.title);

			const eyebrow = document.createElement("div");
			eyebrow.className = "vormbyte-visual-confirmation-eyebrow";
			eyebrow.textContent = "Confirmation required";
			card.appendChild(eyebrow);

			const title = document.createElement("h2");
			title.textContent = confirmation.title;
			card.appendChild(title);

			const description = document.createElement("p");
			description.textContent = confirmation.description;
			card.appendChild(description);

			const details = document.createElement("dl");
			details.className = "vormbyte-visual-confirmation-details";
			for (const detail of confirmation.details) {
				const term = document.createElement("dt");
				if (detail.label === "Reason") term.className = "vormbyte-visual-confirmation-reason-label";
				term.textContent = detail.label;
				details.appendChild(term);

				const value = document.createElement("dd");
				if (detail.label === "Reason") value.className = "vormbyte-visual-confirmation-reason";
				if (detail.format === "code") {
					const code = document.createElement("code");
					code.textContent = detail.value;
					value.appendChild(code);
				} else {
					value.textContent = detail.value;
				}
				details.appendChild(value);
			}
			card.appendChild(details);

			const actions = document.createElement("div");
			actions.className = "vormbyte-visual-confirmation-actions";

			const denyBtn = document.createElement("button");
			denyBtn.type = "button";
			denyBtn.className = "button vormbyte-visual-confirmation-button vormbyte-visual-confirmation-deny";
			denyBtn.textContent = "Deny";
			denyBtn.addEventListener("click", () => this.decideConfirmation(confirmation.id, "deny"));
			actions.appendChild(denyBtn);

			const allowOnceBtn = document.createElement("button");
			allowOnceBtn.type = "button";
			allowOnceBtn.className = "button button-primary vormbyte-visual-confirmation-button vormbyte-visual-confirmation-allow-once";
			allowOnceBtn.textContent = "Allow once";
			allowOnceBtn.addEventListener("click", () => this.decideConfirmation(confirmation.id, "allow_once"));
			actions.appendChild(allowOnceBtn);

			const allowSessionBtn = document.createElement("button");
			allowSessionBtn.type = "button";
			allowSessionBtn.className = "button vormbyte-visual-confirmation-button vormbyte-visual-confirmation-allow-session";
			allowSessionBtn.textContent = "Allow for this session";
			allowSessionBtn.addEventListener("click", () => this.decideConfirmation(confirmation.id, "allow_session"));
			actions.appendChild(allowSessionBtn);

			if (this.isToolAction(confirmation.action)) {
				this.appendAdvancedConfirmationAction(
					actions,
					confirmation.id,
					"Allow all",
					"Allow all tools until this workspace closes",
					"allow_all_tools_session",
				);
			}

			card.appendChild(actions);
			this.confirmationsEl.appendChild(card);
		}
	}

	private appendAdvancedConfirmationAction(
		actions: HTMLDivElement,
		confirmationId: number,
		label: string,
		title: string,
		decision: ConfirmationDecision,
	): void {
		const advancedActions = document.createElement("div");
		advancedActions.className = "vormbyte-visual-confirmation-advanced-actions";

		const button = document.createElement("button");
		button.type = "button";
		button.className = "button button-small vormbyte-visual-confirmation-button vormbyte-visual-confirmation-allow-all";
		button.textContent = label;
		button.title = title;
		button.setAttribute("aria-label", title);
		button.addEventListener("click", () => this.decideConfirmation(confirmationId, decision));
		advancedActions.appendChild(button);
		actions.appendChild(advancedActions);
	}

	private decideConfirmation(id: number, decision: ConfirmationDecision): void {
		const confirmation = this.pendingConfirmations.get(id);
		if (!confirmation) return;

		if (decision === "allow_session") {
			this.sessionAllowedActions.add(confirmation.action);
			for (const item of Array.from(this.pendingConfirmations.values())) {
				if (item.action !== confirmation.action) continue;
				this.pendingConfirmations.delete(item.id);
				this.startConfirmedAction(item.actionId);
			}
			this.host.log(`Allowed ${this.confirmableActionLabel(confirmation.action)} for this session.`);
			this.renderConfirmations();
			return;
		}

		if (decision === "allow_all_tools_session") {
			this.sessionAllowedAllTools = true;
			this.host.setAllToolsAllowed(true);
			for (const item of Array.from(this.pendingConfirmations.values())) {
				if (!this.isToolAction(item.action)) continue;
				this.pendingConfirmations.delete(item.id);
				this.startConfirmedAction(item.actionId);
			}
			this.host.log("Enabled allow-all mode for this workspace.");
			this.renderConfirmations();
			return;
		}

		this.pendingConfirmations.delete(id);
		if (decision === "allow_once") {
			this.startConfirmedAction(confirmation.actionId);
		} else {
			this.setActionError(confirmation.actionId, "denied", `User denied action: ${confirmation.title}`);
		}
		this.host.log(
			decision === "allow_once"
				? `Allowed once: ${confirmation.title}.`
				: `Denied: ${confirmation.title}.`
		);
		this.renderConfirmations();
	}

	private startConfirmedAction(actionId: string): void {
		const action = this.actions.get(actionId);
		if (!action || action.status !== "waiting_for_confirmation") return;

		action.status = "running";
		action.updatedAt = Date.now();
		this.notifyActionWaiters(actionId);

		void action
			.run()
			.then((result) => {
				action.status = "succeeded";
				action.result = cloneSerializable(result);
				action.updatedAt = Date.now();
				this.host.log(`Completed confirmed action ${actionId}.`);
			})
			.catch((err: unknown) => {
				action.status = "failed";
				action.error = err instanceof Error ? err.message : String(err);
				action.updatedAt = Date.now();
				this.host.log(`Confirmed action ${actionId} failed: ${action.error}`);
			})
			.finally(() => this.notifyActionWaiters(actionId));
	}

	private setActionError(actionId: string, status: "failed" | "denied", error: string): void {
		const action = this.actions.get(actionId);
		if (!action) return;
		action.status = status;
		action.error = error;
		action.updatedAt = Date.now();
		this.notifyActionWaiters(actionId);
	}

	private notifyActionWaiters(actionId: string): void {
		const waiters = this.actionWaiters.get(actionId);
		if (!waiters) return;
		for (const waiter of Array.from(waiters)) {
			waiter();
		}
	}

	private getAction(actionId: string): WorkspaceAction {
		const action = this.actions.get(actionId);
		if (!action) {
			throw new Error(`Unknown action: ${actionId}`);
		}
		return action;
	}

	private serializeAction(action: WorkspaceAction): unknown {
		const terminal = this.isTerminalActionStatus(action.status);
		const awaitingApproval = action.status === "waiting_for_confirmation";
		const running = action.status === "running";
		return {
			actionId: action.id,
			action: action.action,
			title: action.title,
			status: action.status,
			terminal,
			requiresUserConfirmation: awaitingApproval,
			message:
				awaitingApproval
					? "Approval is required in Vormbyte Visual before this action can run. Tell the user to approve it in the browser workspace."
					: running
						? "The approved action is running."
					: null,
			agentInstruction: awaitingApproval
				? "Tell the user to approve in Vormbyte Visual before waiting for this action. The user does not need to reply in chat after approving."
				: running
				? "Call workspace_request with method 'workspace_wait_action' and params containing this actionId and timeoutMs 60000. If it is still not terminal, call it again."
				: null,
			nextTool: running
				? {
						name: "workspace_request",
						args: { method: "workspace_wait_action", params: { actionId: action.id, timeoutMs: 60000 } },
					}
				: null,
			createdAt: action.createdAt,
			updatedAt: action.updatedAt,
			result: action.result ?? null,
			error: action.error ?? null,
		};
	}

	private isTerminalActionStatus(status: WorkspaceActionStatus): boolean {
		return status === "succeeded" || status === "failed" || status === "denied";
	}

	private confirmableActionLabel(action: ConfirmableModelAction): string {
		if (action === "workspace_eval") return "JavaScript escape hatch";
		if (action.startsWith("backend_tool:")) return `backend tool ${action.slice("backend_tool:".length)}`;
		if (action.startsWith("page_tool:")) return `page tool ${action.slice("page_tool:".length)}`;
		if (action.startsWith("page_interaction:")) return `page action ${action.slice("page_interaction:".length)}`;
		return action;
	}

	private isToolAction(action: ConfirmableModelAction): boolean {
		return action === "workspace_eval" || action.startsWith("backend_tool:") || action.startsWith("page_tool:") || action.startsWith("page_interaction:");
	}

	private isAllToolsAllowed(action: ConfirmableModelAction): boolean {
		return this.sessionAllowedAllTools && this.isToolAction(action);
	}
}
