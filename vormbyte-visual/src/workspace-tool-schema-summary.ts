export function summarizeToolInput(parameters: unknown, depth = 0): unknown {
	const schema = parameters && typeof parameters === "object" && !Array.isArray(parameters)
		? parameters as { required?: unknown; properties?: unknown; items?: unknown; type?: unknown; description?: unknown }
		: null;
	if (!schema) return {};
	if (!schema.properties && schema.type && schema.type !== "object") {
		return summarizeProperty(schema, depth);
	}

	const required = Array.isArray(schema.required)
		? schema.required.filter((key) => typeof key === "string")
		: [];
	const properties = schema.properties && typeof schema.properties === "object" && !Array.isArray(schema.properties)
		? schema.properties as Record<string, { type?: unknown; description?: unknown }>
		: {};

	const summary: Record<string, unknown> = {
		required,
		properties: Object.fromEntries(
			Object.entries(properties).map(([key, property]) => [
				key,
				summarizeProperty(property, depth),
			]),
		),
	};

	return summary;
}

function summarizeProperty(property: { type?: unknown; description?: unknown; items?: unknown; required?: unknown; properties?: unknown }, depth: number): Record<string, unknown> {
	const summary: Record<string, unknown> = {
		type: property.type,
		description: typeof property.description === "string"
			? property.description.slice(0, 180)
			: undefined,
	};

	if (depth < 2 && property.items) {
		summary.items = summarizeToolInput(property.items, depth + 1);
	}

	if (depth < 2 && property.properties) {
		const nested = summarizeToolInput(property, depth + 1) as Record<string, unknown>;
		summary.required = nested.required;
		summary.properties = nested.properties;
	}

	return summary;
}
