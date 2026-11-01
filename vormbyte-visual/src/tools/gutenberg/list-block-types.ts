import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { wp } from "./gutenberg-helpers";

export const listBlockTypesTool: AgentTool = {
	name: "list_block_types",
	label: "List Block Types",
	description:
		"List available Gutenberg block types. Returns block name and title. " +
		"Use category/search filters to narrow results (e.g. category 'text', 'media', 'design'). " +
		"Call before create_block to find the most specific native block instead of using core/html.",
	parameters: Type.Object({
		category: Type.Optional(
			Type.String({
				description:
					'Filter by block category: "text", "media", "design", "widgets", "embed", etc.',
			}),
		),
		search: Type.Optional(
			Type.String({
				description: "Case-insensitive filter matched against block name and title.",
			}),
		),
	}),
	execute: async (_id, params) => {
		const { category, search } = params as { category?: string; search?: string };
		const needle = typeof search === "string" ? search.trim().toLowerCase() : "";

		const types = wp().blocks.getBlockTypes();
		const lines: string[] = [];
		const categories = new Set<string>();
		for (let i = 0; i < types.length; i++) {
			const t = types[i];
			if (t.category) categories.add(t.category);
			if (category && t.category !== category) continue;
			if (needle && !`${t.name} ${t.title || ""}`.toLowerCase().includes(needle)) continue;
			lines.push(`${t.name} — ${t.title || t.name}`);
		}

		let text = lines.join("\n");
		if (needle) text = `Search: ${search}\n\n` + text;
		if (category) {
			text = `Category: ${category}\n` + text;
		} else {
			text += "\n\nCategories: " + Array.from(categories).sort().join(", ");
		}

		return {
			content: [{ type: "text" as const, text }],
			details: { count: lines.length },
		};
	},
};
