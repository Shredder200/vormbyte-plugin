export function objectParams(params: unknown): Record<string, unknown> {
	if (!params || typeof params !== "object" || Array.isArray(params)) {
		return {};
	}
	return params as Record<string, unknown>;
}

export function requiredString(params: unknown, key: string): string {
	const value = objectParams(params)[key];
	if (typeof value !== "string" || !value.trim()) {
		throw new Error(`Missing required string param: ${key}`);
	}
	return value;
}

export function firstString(...values: unknown[]): string {
	for (const value of values) {
		if (typeof value === "string" && value.trim()) return value.trim();
	}
	return "";
}

export function optionalTimeoutMs(params: unknown): number {
	const value = objectParams(params).timeoutMs;
	if (typeof value !== "number" || !Number.isFinite(value)) return 60000;
	return Math.max(0, Math.min(Math.floor(value), 120000));
}

export function optionalTextLimit(params: Record<string, unknown>): number {
	const value = params.textLimit;
	if (typeof value !== "number" || !Number.isFinite(value)) return 2000;
	return Math.max(0, Math.min(Math.floor(value), 8000));
}

export function backendToolName(params: Record<string, unknown>): string {
	for (const key of ["name", "toolName", "abilityName"]) {
		const value = params[key];
		if (typeof value === "string" && value.trim()) return value.trim();
	}

	throw new Error("Missing required string param: name");
}

/**
 * Some MCP clients double-encode tool arguments, so a reason like
 * Create "Venezia" arrives as Create \"Venezia\" with literal backslashes. When
 * a string carries JSON-escaped quotes, decode it once; a clean string has no
 * escaped quotes and is returned unchanged (idempotent).
 */
export function decodeJsonEscaped(value: string): string {
	if (!value.includes('\\"')) return value;
	try {
		const decoded: unknown = JSON.parse(`"${value}"`);
		return typeof decoded === "string" ? decoded : value;
	} catch {
		return value;
	}
}

export function optionalReason(params: Record<string, unknown>): string {
	const reason =
		typeof params.reason === "string" ? decodeJsonEscaped(params.reason).replace(/\s+/g, " ").trim() : "";
	return reason ? truncateText(reason, 240) : "No reason question provided by the agent.";
}

export function truncateText(value: string, maxLength: number): string {
	return value.length <= maxLength ? value : `${value.slice(0, maxLength - 3)}...`;
}

export function cloneSerializable(value: unknown): unknown {
	if (value === undefined) return null;
	try {
		return JSON.parse(JSON.stringify(value)) as unknown;
	} catch {
		return String(value);
	}
}
