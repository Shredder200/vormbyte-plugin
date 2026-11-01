import type { PageElementRef, PageElementRefStore, PageSnapshotItem, WorkspacePage } from "./workspace-types";

interface WorkspacePageToolsHost {
	pageElementRefs: Map<string, PageElementRefStore>;
	getPage: (pageId: string) => WorkspacePage;
	getPageWindow: (page: WorkspacePage) => Window;
	markAgentAction: (pageId: string) => void;
}

export class WorkspacePageTools {
	constructor(private host: WorkspacePageToolsHost) {}

	snapshotGenericPage(pageId: string, params: Record<string, unknown>, agentAction = false): unknown {
		const page = this.host.getPage(pageId);
		if (agentAction) this.host.markAgentAction(pageId);
		const win = this.host.getPageWindow(page);
		const doc = win.document;
		const root = this.pageSnapshotRoot(doc, params);
		const interactiveOnly = this.optionalBoolean(params, ["interactive_only", "interactiveOnly"], true);
		const includeText = this.optionalBoolean(params, ["include_text", "includeText"], !interactiveOnly);
		const includeUrls = this.optionalBoolean(params, ["include_urls", "includeUrls"], false);
		const maxElements = this.optionalInteger(params, ["max_elements", "maxElements"], 60, 1, 250);
		const maxTextChars = this.optionalInteger(params, ["max_text_chars", "maxTextChars"], 120, 20, 500);
		const includeDetails = this.optionalBoolean(params, ["include_details", "includeDetails"], false);
		const built = this.buildPageSnapshot(win, root, {
			interactiveOnly,
			includeText,
			includeUrls,
			maxElements,
			maxTextChars,
		});

		const refs = new Map<string, PageElementRef>();
		const currentUrl = win.location.href;
		for (const entry of built.entries) {
			refs.set(entry.item.ref, {
				ref: entry.item.ref,
				element: entry.element,
				url: currentUrl,
				role: entry.item.role,
				name: entry.item.name,
				selector: entry.item.selector,
			});
		}
		this.host.pageElementRefs.set(page.id, { createdAt: Date.now(), url: currentUrl, refs });

		const lines = [
			`Page: ${doc.title || page.title}`,
			`URL: ${currentUrl}`,
			"Refs are valid until the page changes. Re-run workspace_page_snapshot after navigation or DOM changes.",
			"",
			...built.entries.map((entry) => this.formatSnapshotItem(entry.item)),
		];
		if (built.truncated) lines.push(`... truncated after ${maxElements} elements`);

		const result: Record<string, unknown> = {
			pageId: page.id,
			title: doc.title || page.title,
			url: currentUrl,
			interactiveOnly,
			count: built.entries.length,
			truncated: built.truncated,
			snapshot: lines.join("\n"),
		};
		if (includeDetails) result.refs = built.entries.map((entry) => entry.item);
		return result;
	}

	getGenericPageText(pageId: string, params: Record<string, unknown>, agentAction = false): unknown {
		const page = this.host.getPage(pageId);
		if (agentAction) this.host.markAgentAction(pageId);
		const element = this.resolveOptionalPageTarget(page, params);
		const limit = this.optionalInteger(params, ["max_text_chars", "maxTextChars", "textLimit"], 2000, 0, 20000);
		const text = this.elementText(element);

		return {
			pageId: page.id,
			url: this.safePageUrl(page),
			target: this.describePageElement(page, element),
			truncated: limit > 0 && text.length > limit,
			text: limit === 0 ? "" : this.truncateText(text, limit),
		};
	}

	getGenericPageHtml(pageId: string, params: Record<string, unknown>, agentAction = false): unknown {
		const page = this.host.getPage(pageId);
		if (agentAction) this.host.markAgentAction(pageId);
		const element = this.resolveOptionalPageTarget(page, params);
		const limit = this.optionalInteger(params, ["max_html_chars", "maxHtmlChars", "max_text_chars", "maxTextChars"], 3000, 0, 30000);
		const html = (element as Element).innerHTML ?? "";

		return {
			pageId: page.id,
			url: this.safePageUrl(page),
			target: this.describePageElement(page, element),
			truncated: limit > 0 && html.length > limit,
			html: limit === 0 ? "" : this.truncateText(html, limit),
		};
	}

	getGenericPageAttr(pageId: string, params: Record<string, unknown>, agentAction = false): unknown {
		const page = this.host.getPage(pageId);
		if (agentAction) this.host.markAgentAction(pageId);
		const name = this.firstString(params.name, params.attr, params.attribute);
		if (!name) throw new Error("workspace_page_get_attr requires a name parameter.");
		const element = this.resolvePageTarget(page, params);

		return {
			pageId: page.id,
			url: this.safePageUrl(page),
			target: this.describePageElement(page, element),
			name,
			value: element.getAttribute(name),
		};
	}

	getGenericPageValue(pageId: string, params: Record<string, unknown>, agentAction = false): unknown {
		const page = this.host.getPage(pageId);
		if (agentAction) this.host.markAgentAction(pageId);
		const element = this.resolvePageTarget(page, params);

		return {
			pageId: page.id,
			url: this.safePageUrl(page),
			target: this.describePageElement(page, element),
			value: this.readElementValue(element),
		};
	}

	async waitForGenericPage(pageId: string, params: Record<string, unknown>, agentAction = false): Promise<unknown> {
		const page = this.host.getPage(pageId);
		if (agentAction) this.host.markAgentAction(pageId);
		const timeoutMs = this.optionalInteger(params, ["timeoutMs", "timeout_ms"], 25000, 0, 120000);
		const intervalMs = this.optionalInteger(params, ["intervalMs", "interval_ms"], 100, 25, 2000);
		const waitOnlyMs = this.optionalInteger(params, ["ms", "milliseconds"], -1, -1, 120000);
		const started = Date.now();

		if (!this.hasWaitCondition(params)) {
			if (waitOnlyMs < 0) {
				throw new Error("workspace_page_wait requires text, target/selector, url, load, or ms.");
			}
			await this.delay(waitOnlyMs);
			return { pageId: page.id, waitedMs: Date.now() - started, matched: { ms: waitOnlyMs } };
		}

		while (true) {
			const match = this.matchGenericPageWait(page, params);
			if (match.ok) {
				return {
					pageId: page.id,
					url: this.safePageUrl(page),
					waitedMs: Date.now() - started,
					matched: match.matched,
				};
			}

			if (Date.now() - started >= timeoutMs) {
				throw new Error(`Timed out after ${timeoutMs}ms waiting for page condition: ${match.pending.join(", ")}`);
			}

			await this.delay(intervalMs);
		}
	}

	clickGenericPageTarget(pageId: string, params: Record<string, unknown>): unknown {
		const page = this.host.getPage(pageId);
		const element = this.resolvePageTarget(page, params);
		this.scrollElementIntoView(element);
		this.focusElement(element);
		(element as HTMLElement).click();
		return this.genericPageActionResult(page, "clicked", element);
	}

	fillGenericPageTarget(pageId: string, params: Record<string, unknown>): unknown {
		const page = this.host.getPage(pageId);
		const element = this.resolvePageTarget(page, params);
		const value = this.requiredValueString(params, ["value", "text"]);
		this.scrollElementIntoView(element);
		this.focusElement(element);
		this.setElementTextValue(element, value, false);
		return this.genericPageActionResult(page, "filled", element);
	}

	typeGenericPageTarget(pageId: string, params: Record<string, unknown>): unknown {
		const page = this.host.getPage(pageId);
		const element = this.resolvePageTarget(page, params);
		const text = this.requiredValueString(params, ["text", "value"]);
		this.scrollElementIntoView(element);
		this.focusElement(element);
		this.setElementTextValue(element, text, true);
		return this.genericPageActionResult(page, "typed", element);
	}

	pressGenericPageKey(pageId: string, params: Record<string, unknown>): unknown {
		const page = this.host.getPage(pageId);
		const win = this.host.getPageWindow(page);
		const target = this.optionalTarget(params);
		const element = target ? this.resolvePageTarget(page, params) : (win.document.activeElement || win.document.body || win.document.documentElement);
		const key = this.firstString(params.key, params.keys);
		if (!key) throw new Error("workspace_page_press requires a key parameter.");
		this.scrollElementIntoView(element);
		this.focusElement(element);

		const eventInit: KeyboardEventInit = {
			key,
			bubbles: true,
			cancelable: true,
			altKey: params.altKey === true || params.alt_key === true,
			ctrlKey: params.ctrlKey === true || params.ctrl_key === true,
			metaKey: params.metaKey === true || params.meta_key === true,
			shiftKey: params.shiftKey === true || params.shift_key === true,
		};
		const KeyboardEventCtor = (win as Window & typeof globalThis).KeyboardEvent;
		const keydown = new KeyboardEventCtor("keydown", eventInit);
		const proceed = element.dispatchEvent(keydown);
		if (proceed && key.length === 1) {
			element.dispatchEvent(new KeyboardEventCtor("keypress", eventInit));
		}
		if (proceed) this.applySimpleKeyDefault(element, key);
		element.dispatchEvent(new KeyboardEventCtor("keyup", eventInit));

		return this.genericPageActionResult(page, `pressed ${key}`, element);
	}

	selectGenericPageOption(pageId: string, params: Record<string, unknown>): unknown {
		const page = this.host.getPage(pageId);
		const element = this.resolvePageTarget(page, params);
		if (this.elementTag(element) !== "select") {
			throw new Error("workspace_page_select target must be a <select> element.");
		}
		const select = element as HTMLSelectElement;
		const values = this.selectValues(params);
		if (!select.multiple && values.length > 1) {
			throw new Error("Cannot select multiple values on a single-select element.");
		}

		const valueSet = new Set(values);
		let matched = 0;
		for (const option of Array.from(select.options)) {
			const shouldSelect = valueSet.has(option.value) || valueSet.has(option.text);
			option.selected = shouldSelect;
			if (shouldSelect) matched++;
		}
		if (matched === 0) throw new Error(`No select option matched: ${values.join(", ")}`);
		this.dispatchInputAndChange(element);
		return this.genericPageActionResult(page, "selected", element);
	}

	setGenericPageChecked(pageId: string, params: Record<string, unknown>, checked: boolean): unknown {
		const page = this.host.getPage(pageId);
		const element = this.resolvePageTarget(page, params);
		if (this.elementTag(element) !== "input") {
			throw new Error(`${checked ? "workspace_page_check" : "workspace_page_uncheck"} target must be an input element.`);
		}
		const input = element as HTMLInputElement;
		const type = (input.type || input.getAttribute("type") || "").toLowerCase();
		if (type !== "checkbox" && type !== "radio") {
			throw new Error(`${checked ? "workspace_page_check" : "workspace_page_uncheck"} target must be a checkbox or radio input.`);
		}
		input.checked = checked;
		this.dispatchInputAndChange(element);
		return this.genericPageActionResult(page, checked ? "checked" : "unchecked", element);
	}

	scrollGenericPage(pageId: string, params: Record<string, unknown>): unknown {
		const page = this.host.getPage(pageId);
		const win = this.host.getPageWindow(page);
		const target = this.optionalTarget(params);
		const element = target ? this.resolvePageTarget(page, params) : null;
		const intoView = this.optionalBoolean(params, ["into_view", "intoView"], Boolean(element && params.direction === undefined && params.x === undefined && params.y === undefined));
		if (element && intoView) {
			this.scrollElementIntoView(element);
			return this.genericPageActionResult(page, "scrolled into view", element);
		}

		const amount = this.optionalInteger(params, ["amount", "pixels"], 600, 1, 10000);
		const direction = this.firstString(params.direction) || "down";
		const explicitX = this.optionalNumber(params, ["x", "left"]);
		const explicitY = this.optionalNumber(params, ["y", "top"]);
		const delta = this.scrollDelta(direction, amount);
		const left = explicitX ?? delta.left;
		const top = explicitY ?? delta.top;
		if (element) {
			(element as HTMLElement).scrollBy({ left, top, behavior: "auto" });
		} else {
			win.scrollBy({ left, top, behavior: "auto" });
		}

		return {
			ok: true,
			action: "scrolled",
			pageId: page.id,
			url: this.safePageUrl(page),
			target: element ? this.describePageElement(page, element) : "window",
			delta: { left, top },
		};
	}

	private pageSnapshotRoot(doc: Document, params: Record<string, unknown>): Element {
		const selector = this.firstString(params.selector, params.scope);
		if (!selector) return doc.body || doc.documentElement;

		let root: Element | null;
		try {
			root = doc.querySelector(selector);
		} catch (error) {
			const message = error instanceof Error ? error.message : String(error);
			throw new Error(`Invalid snapshot selector ${JSON.stringify(selector)}: ${message}`, { cause: error });
		}
		if (!root) throw new Error(`No element matches snapshot selector: ${selector}`);
		return root;
	}

	private buildPageSnapshot(
		win: Window,
		root: Element,
		options: {
			interactiveOnly: boolean;
			includeText: boolean;
			includeUrls: boolean;
			maxElements: number;
			maxTextChars: number;
		},
	): { entries: Array<{ item: PageSnapshotItem; element: Element }>; truncated: boolean } {
		const elements = [root, ...Array.from(root.querySelectorAll("*"))];
		const selected: Element[] = [];
		let truncated = false;

		for (const element of elements) {
			if (!this.isSnapshotCandidate(win, element, options.interactiveOnly, options.includeText)) continue;
			if (selected.length >= options.maxElements) {
				truncated = true;
				break;
			}
			selected.push(element);
		}

		const selectedSet = new Set(selected);
		return {
			truncated,
			entries: selected.map((element, index) => {
				const role = this.elementRole(element);
				const name = this.truncateText(this.elementName(element), options.maxTextChars);
				const item: PageSnapshotItem = {
					ref: `@e${index + 1}`,
					role,
					tag: this.elementTag(element),
					name,
					selector: this.cssPath(element),
					depth: this.snapshotDepth(element, selectedSet),
					attributes: this.snapshotAttributes(element, options.includeUrls),
				};
				return { item, element };
			}),
		};
	}

	private isSnapshotCandidate(win: Window, element: Element, interactiveOnly: boolean, includeText: boolean): boolean {
		const tag = this.elementTag(element);
		if (["html", "head", "body", "script", "style", "meta", "link", "template", "noscript"].includes(tag)) return false;
		if (tag === "input" && ((element as HTMLInputElement).type || element.getAttribute("type") || "").toLowerCase() === "hidden") return false;
		if (!this.isVisibleElement(win, element)) return false;

		const role = this.elementRole(element);
		const targetable = this.isTargetableElement(element, role);
		if (interactiveOnly) return targetable || role === "heading";
		if (targetable || role === "heading" || role === "image") return true;
		if (!includeText) return false;

		const directText = this.directElementText(element);
		if (directText) return true;

		return ["p", "span", "li", "td", "th", "dt", "dd", "figcaption", "blockquote"].includes(tag) && this.elementText(element) !== "";
	}

	private isVisibleElement(win: Window, element: Element): boolean {
		let current: Element | null = element;
		while (current) {
			if (current.getAttribute("hidden") !== null || current.getAttribute("aria-hidden") === "true") return false;
			const style = win.getComputedStyle(current);
			if (style.display === "none" || style.visibility === "hidden" || style.visibility === "collapse") return false;
			current = current.parentElement;
		}

		const rects = element.getClientRects();
		return rects.length > 0 || this.isTargetableElement(element, this.elementRole(element)) || this.elementText(element) !== "";
	}

	private isTargetableElement(element: Element, role: string): boolean {
		const tag = this.elementTag(element);
		if (tag === "a" && element.getAttribute("href")) return true;
		if (["button", "select", "textarea", "summary"].includes(tag)) return true;
		if (tag === "input") return ((element as HTMLInputElement).type || element.getAttribute("type") || "text").toLowerCase() !== "hidden";
		if ((element as HTMLElement).isContentEditable) return true;
		if (["button", "link", "checkbox", "radio", "textbox", "combobox", "listbox", "menuitem", "tab", "switch"].includes(role)) return true;
		const tabindex = element.getAttribute("tabindex");
		return tabindex !== null && tabindex !== "-1";
	}

	private elementRole(element: Element): string {
		const explicitRole = this.firstString(element.getAttribute("role"));
		if (explicitRole) return explicitRole.split(/\s+/)[0] || "generic";

		const tag = this.elementTag(element);
		if (tag === "a" && element.getAttribute("href")) return "link";
		if (tag === "button") return "button";
		if (tag === "textarea") return "textbox";
		if (tag === "select") return (element as HTMLSelectElement).multiple ? "listbox" : "combobox";
		if (/^h[1-6]$/.test(tag)) return "heading";
		if (tag === "img") return "image";
		if (tag === "label") return "label";
		if (tag === "form") return "form";
		if (tag === "summary") return "button";
		if (tag === "input") {
			const type = ((element as HTMLInputElement).type || element.getAttribute("type") || "text").toLowerCase();
			if (["button", "submit", "reset", "image"].includes(type)) return "button";
			if (type === "checkbox") return "checkbox";
			if (type === "radio") return "radio";
			if (type === "range") return "slider";
			if (type === "number") return "spinbutton";
			if (type === "hidden") return "hidden";
			return "textbox";
		}

		return tag || "generic";
	}

	private snapshotAttributes(element: Element, includeUrls: boolean): Record<string, string | boolean | number> {
		const attrs: Record<string, string | boolean | number> = {};
		const tag = this.elementTag(element);
		const explicitRole = this.firstString(element.getAttribute("role"));
		if (explicitRole) attrs.role = explicitRole;
		if ((element as HTMLButtonElement).disabled === true || element.getAttribute("aria-disabled") === "true") attrs.disabled = true;
		const expanded = element.getAttribute("aria-expanded");
		if (expanded !== null) attrs.expanded = expanded;

		if (tag === "a" && includeUrls) attrs.href = (element as HTMLAnchorElement).href || element.getAttribute("href") || "";
		if (tag === "input") {
			const input = element as HTMLInputElement;
			const type = (input.type || input.getAttribute("type") || "text").toLowerCase();
			attrs.type = type;
			if (input.placeholder) attrs.placeholder = input.placeholder;
			if (type === "checkbox" || type === "radio") attrs.checked = input.checked;
			if (["button", "submit", "reset"].includes(type) && input.value) attrs.value = input.value;
		}
		if (tag === "textarea" && (element as HTMLTextAreaElement).placeholder) attrs.placeholder = (element as HTMLTextAreaElement).placeholder;
		if (tag === "select" && (element as HTMLSelectElement).multiple) attrs.multiple = true;
		return attrs;
	}

	private formatSnapshotItem(item: PageSnapshotItem): string {
		const indent = "  ".repeat(Math.min(item.depth, 8));
		const attrs = Object.entries(item.attributes)
			.map(([key, value]) => `${key}=${JSON.stringify(value)}`)
			.join(" ");
		const label = item.name ? ` ${JSON.stringify(item.name)}` : "";
		return `${indent}${item.ref} [${item.role} ${item.tag}]${attrs ? ` ${attrs}` : ""}${label}`;
	}

	private snapshotDepth(element: Element, selected: Set<Element>): number {
		let depth = 0;
		let parent = element.parentElement;
		while (parent) {
			if (selected.has(parent)) depth++;
			parent = parent.parentElement;
		}
		return depth;
	}

	private resolveOptionalPageTarget(page: WorkspacePage, params: Record<string, unknown>): Element {
		return this.optionalTarget(params) ? this.resolvePageTarget(page, params) : this.host.getPageWindow(page).document.body || this.host.getPageWindow(page).document.documentElement;
	}

	private resolvePageTarget(page: WorkspacePage, params: Record<string, unknown>): Element {
		const target = this.optionalTarget(params);
		if (!target) throw new Error("A target, ref, or selector parameter is required.");
		const win = this.host.getPageWindow(page);
		const doc = win.document;

		if (/^@e\d+$/.test(target)) {
			const store = this.host.pageElementRefs.get(page.id);
			if (!store) throw new Error(`No snapshot refs are available for ${page.id}. Call workspace_page_snapshot first.`);
			const stored = store.refs.get(target);
			if (!stored) throw new Error(`Unknown snapshot ref ${target}. Re-run workspace_page_snapshot.`);
			if (stored.url !== win.location.href) throw new Error(`Snapshot ref ${target} is stale because the page URL changed. Re-run workspace_page_snapshot.`);
			if (stored.element.ownerDocument !== doc || (stored.element as { isConnected?: boolean }).isConnected === false) {
				throw new Error(`Snapshot ref ${target} is stale because the element is no longer connected. Re-run workspace_page_snapshot.`);
			}
			return stored.element;
		}

		try {
			const element = doc.querySelector(target);
			if (!element) throw new Error(`No element matches selector: ${target}`);
			return element;
		} catch (error) {
			if (error instanceof Error && error.message.startsWith("No element matches")) throw error;
			const message = error instanceof Error ? error.message : String(error);
			throw new Error(`Invalid selector ${JSON.stringify(target)}: ${message}`, { cause: error });
		}
	}

	private optionalTarget(params: Record<string, unknown>): string {
		return this.firstString(params.target, params.ref, params.selector);
	}

	private describePageElement(page: WorkspacePage, element: Element): Record<string, unknown> {
		return {
			ref: this.refForElement(page.id, element),
			role: this.elementRole(element),
			tag: this.elementTag(element),
			name: this.truncateText(this.elementName(element), 160),
			selector: this.cssPath(element),
		};
	}

	private refForElement(pageId: string, element: Element): string | null {
		const store = this.host.pageElementRefs.get(pageId);
		if (!store) return null;
		for (const ref of store.refs.values()) {
			if (ref.element === element) return ref.ref;
		}
		return null;
	}

	private genericPageActionResult(page: WorkspacePage, action: string, element: Element): Record<string, unknown> {
		return {
			ok: true,
			action,
			pageId: page.id,
			url: this.safePageUrl(page),
			target: this.describePageElement(page, element),
		};
	}

	private readElementValue(element: Element): unknown {
		const tag = this.elementTag(element);
		if (tag === "select") {
			const select = element as HTMLSelectElement;
			const values = Array.from(select.selectedOptions).map((option) => option.value);
			return select.multiple ? values : values[0] ?? "";
		}
		if (tag === "textarea") return (element as HTMLTextAreaElement).value;
		if (tag === "input") {
			const input = element as HTMLInputElement;
			const type = (input.type || input.getAttribute("type") || "text").toLowerCase();
			if (type === "password") return "[redacted password value]";
			if (type === "checkbox" || type === "radio") return { value: input.value, checked: input.checked };
			return input.value;
		}
		if ((element as HTMLElement).isContentEditable) return this.elementText(element);
		throw new Error(`Element ${this.cssPath(element)} does not expose a form value.`);
	}

	private setElementTextValue(element: Element, value: string, append: boolean): void {
		const tag = this.elementTag(element);
		if (tag === "textarea" || tag === "input") {
			const input = element as HTMLInputElement | HTMLTextAreaElement;
			if (tag === "input") {
				const type = ((input as HTMLInputElement).type || element.getAttribute("type") || "text").toLowerCase();
				if (["checkbox", "radio", "file", "hidden", "button", "submit", "reset", "image"].includes(type)) {
					throw new Error(`Cannot fill input type ${type}. Use a more specific page interaction method.`);
				}
			}

			const current = input.value || "";
			let next = value;
			let nextSelection = value.length;
			if (append) {
				const start = typeof input.selectionStart === "number" ? input.selectionStart : current.length;
				const end = typeof input.selectionEnd === "number" ? input.selectionEnd : start;
				next = `${current.slice(0, start)}${value}${current.slice(end)}`;
				nextSelection = start + value.length;
			}
			this.setNativeValue(element, next);
			try {
				input.setSelectionRange(nextSelection, nextSelection);
			} catch {
				// Some input types do not support selection ranges.
			}
			this.dispatchInputAndChange(element);
			return;
		}

		if ((element as HTMLElement).isContentEditable) {
			const current = this.elementText(element);
			element.textContent = append ? current + value : value;
			this.dispatchInputAndChange(element);
			return;
		}

		throw new Error(`Element ${this.cssPath(element)} is not fillable.`);
	}

	private setNativeValue(element: Element, value: string): void {
		const view = element.ownerDocument.defaultView;
		const tag = this.elementTag(element);
		const prototype = tag === "textarea"
			? view?.HTMLTextAreaElement?.prototype
			: view?.HTMLInputElement?.prototype;
		const descriptor = prototype ? Object.getOwnPropertyDescriptor(prototype, "value") : null;
		if (descriptor?.set) {
			descriptor.set.call(element, value);
		} else {
			(element as HTMLInputElement | HTMLTextAreaElement).value = value;
		}
	}

	private dispatchInputAndChange(element: Element): void {
		const view = element.ownerDocument.defaultView || window;
		element.dispatchEvent(new view.Event("input", { bubbles: true }));
		element.dispatchEvent(new view.Event("change", { bubbles: true }));
	}

	private focusElement(element: Element): void {
		const focusable = element as HTMLElement & { focus?: (options?: FocusOptions) => void };
		try {
			focusable.focus?.({ preventScroll: true });
		} catch {
			focusable.focus?.();
		}
	}

	private scrollElementIntoView(element: Element): void {
		(element as HTMLElement).scrollIntoView?.({ block: "center", inline: "center", behavior: "auto" });
	}

	private applySimpleKeyDefault(element: Element, key: string): void {
		const normalized = key === "Spacebar" ? " " : key;
		const tag = this.elementTag(element);
		if (normalized === "Enter") {
			if (tag === "a" || tag === "button") {
				(element as HTMLElement).click();
				return;
			}
			if (tag === "input") {
				const input = element as HTMLInputElement;
				const type = (input.type || input.getAttribute("type") || "text").toLowerCase();
				if (["button", "submit", "reset"].includes(type)) {
					input.click();
					return;
				}
				if (input.form) input.form.requestSubmit();
			}
		}

		if ((normalized === " " || normalized === "Space") && tag === "input") {
			const input = element as HTMLInputElement;
			const type = (input.type || input.getAttribute("type") || "text").toLowerCase();
			if (type === "checkbox") {
				input.checked = !input.checked;
				this.dispatchInputAndChange(element);
			}
		}
	}

	private selectValues(params: Record<string, unknown>): string[] {
		const values = params.values;
		if (Array.isArray(values)) return values.map((value) => String(value));
		return [this.requiredValueString(params, ["value"])]
			.map((value) => value.trim())
			.filter((value) => value !== "");
	}

	private scrollDelta(direction: string, amount: number): { left: number; top: number } {
		switch (direction.toLowerCase()) {
			case "up":
				return { left: 0, top: -amount };
			case "left":
				return { left: -amount, top: 0 };
			case "right":
				return { left: amount, top: 0 };
			case "down":
			default:
				return { left: 0, top: amount };
		}
	}

	private hasWaitCondition(params: Record<string, unknown>): boolean {
		return Boolean(
			this.firstString(params.text, params.target, params.ref, params.selector, params.url, params.load) || params.load === true,
		);
	}

	private matchGenericPageWait(page: WorkspacePage, params: Record<string, unknown>): { ok: boolean; matched: Record<string, unknown>; pending: string[] } {
		const win = this.host.getPageWindow(page);
		const matched: Record<string, unknown> = {};
		const pending: string[] = [];
		const load = params.load === true ? "complete" : this.firstString(params.load);
		if (load) {
			if (this.loadStateMatches(win.document.readyState, load)) matched.load = load;
			else pending.push(`load=${load}`);
		}

		const url = this.firstString(params.url);
		if (url) {
			if (this.globMatches(url, win.location.href)) matched.url = win.location.href;
			else pending.push(`url=${url}`);
		}

		const text = this.firstString(params.text);
		if (text) {
			const bodyText = win.document.body?.innerText ?? "";
			const caseSensitive = this.optionalBoolean(params, ["case_sensitive", "caseSensitive"], false);
			const haystack = caseSensitive ? bodyText : bodyText.toLowerCase();
			const needle = caseSensitive ? text : text.toLowerCase();
			if (haystack.includes(needle)) matched.text = text;
			else pending.push(`text=${text}`);
		}

		if (this.optionalTarget(params)) {
			try {
				const element = this.resolvePageTarget(page, params);
				if (this.isVisibleElement(win, element)) matched.target = this.describePageElement(page, element);
				else pending.push(`target=${this.optionalTarget(params)}`);
			} catch {
				pending.push(`target=${this.optionalTarget(params)}`);
			}
		}

		return { ok: pending.length === 0, matched, pending };
	}

	private loadStateMatches(readyState: DocumentReadyState, expected: string): boolean {
		switch (expected.toLowerCase()) {
			case "domcontentloaded":
			case "interactive":
				return readyState === "interactive" || readyState === "complete";
			case "load":
			case "complete":
			case "networkidle":
				return readyState === "complete";
			default:
				return readyState === expected;
		}
	}

	private globMatches(pattern: string, value: string): boolean {
		const escaped = pattern.replace(/[.+^${}()|[\]\\]/g, "\\$&").replace(/\*\*/g, "__DOUBLE_STAR__").replace(/\*/g, "[^/]*").replace(/__DOUBLE_STAR__/g, ".*");
		return new RegExp(`^${escaped}$`).test(value);
	}

	private elementName(element: Element): string {
		const aria = this.firstString(element.getAttribute("aria-label"), this.ariaLabelledByText(element));
		if (aria) return aria;
		const tag = this.elementTag(element);
		if (tag === "input") {
			const input = element as HTMLInputElement;
			const type = (input.type || input.getAttribute("type") || "text").toLowerCase();
			const label = this.associatedLabelText(element);
			if (["button", "submit", "reset"].includes(type)) return this.firstString(input.value, label, type);
			if (type === "password") return this.firstString(label, input.placeholder, "password field");
			return this.firstString(label, input.placeholder, input.value, type);
		}
		if (tag === "textarea") {
			const textarea = element as HTMLTextAreaElement;
			return this.firstString(this.associatedLabelText(element), textarea.placeholder, textarea.value);
		}
		if (tag === "select") {
			const select = element as HTMLSelectElement;
			return this.firstString(this.associatedLabelText(element), Array.from(select.selectedOptions).map((option) => option.text).join(", "));
		}
		if (tag === "img") return this.firstString(element.getAttribute("alt"), element.getAttribute("title"));
		return this.firstString(this.directElementText(element), this.elementText(element), element.getAttribute("title"));
	}

	private associatedLabelText(element: Element): string {
		const labels = (element as { labels?: NodeListOf<HTMLLabelElement> | null }).labels;
		if (labels && labels.length > 0) {
			return Array.from(labels).map((label) => this.elementText(label)).filter(Boolean).join(" ");
		}
		const closestLabel = element.closest("label");
		return closestLabel ? this.elementText(closestLabel) : "";
	}

	private ariaLabelledByText(element: Element): string {
		const ids = this.firstString(element.getAttribute("aria-labelledby"));
		if (!ids) return "";
		return ids
			.split(/\s+/)
			.map((id) => element.ownerDocument.getElementById(id))
			.filter((item): item is HTMLElement => Boolean(item))
			.map((item) => this.elementText(item))
			.filter(Boolean)
			.join(" ");
	}

	private elementText(element: Element): string {
		const text = typeof (element as HTMLElement).innerText === "string" ? (element as HTMLElement).innerText : element.textContent || "";
		return text.replace(/\s+/g, " ").trim();
	}

	private directElementText(element: Element): string {
		return Array.from(element.childNodes)
			.filter((node) => node.nodeType === 3)
			.map((node) => node.textContent || "")
			.join(" ")
			.replace(/\s+/g, " ")
			.trim();
	}

	private elementTag(element: Element): string {
		return element.tagName.toLowerCase();
	}

	private cssPath(element: Element): string {
		const id = element.getAttribute("id");
		if (id) return `#${this.cssEscape(id)}`;

		const parts: string[] = [];
		let current: Element | null = element;
		while (current && current.nodeType === 1 && parts.length < 6) {
			const tag = this.elementTag(current);
			if (tag === "html") break;
			let part = tag;
			const classes = Array.from(current.classList).slice(0, 2).map((className) => `.${this.cssEscape(className)}`).join("");
			part += classes;
			const parent: Element | null = current.parentElement;
			if (parent) {
				const siblings = Array.from(parent.children).filter((child): child is Element => this.elementTag(child) === tag);
				if (siblings.length > 1) part += `:nth-of-type(${siblings.indexOf(current) + 1})`;
			}
			parts.unshift(part);
			current = parent;
		}

		return parts.join(" > ") || this.elementTag(element);
	}

	private cssEscape(value: string): string {
		if (typeof CSS !== "undefined" && typeof CSS.escape === "function") return CSS.escape(value);
		return value.replace(/[^a-zA-Z0-9_-]/g, "\\$&");
	}

	private safePageUrl(page: WorkspacePage): string {
		try {
			return this.host.getPageWindow(page).location.href;
		} catch {
			return page.url;
		}
	}

	private firstString(...values: unknown[]): string {
		for (const value of values) {
			if (typeof value === "string" && value.trim()) return value.trim();
		}
		return "";
	}

	private requiredValueString(params: Record<string, unknown>, keys: string[]): string {
		for (const key of keys) {
			if (Object.prototype.hasOwnProperty.call(params, key) && params[key] !== undefined && params[key] !== null) {
				return String(params[key]);
			}
		}
		throw new Error(`Missing required value parameter: ${keys.join(" or ")}`);
	}

	private optionalBoolean(params: Record<string, unknown>, keys: string[], defaultValue: boolean): boolean {
		for (const key of keys) {
			const value = params[key];
			if (typeof value === "boolean") return value;
			if (typeof value === "string" && value.trim()) return value === "true" || value === "1";
		}
		return defaultValue;
	}

	private optionalInteger(params: Record<string, unknown>, keys: string[], defaultValue: number, min: number, max: number): number {
		for (const key of keys) {
			const raw = params[key];
			const value = typeof raw === "number" ? raw : typeof raw === "string" && raw.trim() ? Number(raw) : NaN;
			if (Number.isFinite(value)) return Math.max(min, Math.min(Math.floor(value), max));
		}
		return defaultValue;
	}

	private optionalNumber(params: Record<string, unknown>, keys: string[]): number | null {
		for (const key of keys) {
			const raw = params[key];
			const value = typeof raw === "number" ? raw : typeof raw === "string" && raw.trim() ? Number(raw) : NaN;
			if (Number.isFinite(value)) return value;
		}
		return null;
	}

	private delay(ms: number): Promise<void> {
		return new Promise((resolve) => window.setTimeout(resolve, ms));
	}

	private truncateText(value: string, maxLength: number): string {
		return value.length <= maxLength ? value : `${value.slice(0, maxLength - 3)}...`;
	}
}
