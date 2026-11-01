import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { wp } from "./gutenberg-helpers";

export const getBlockSchemaTool: AgentTool = {
	name: "get_block_schema",
	label: "Block Schema",
	description:
		"Get a Gutenberg block schema. Defaults to compact attribute summaries for discovery; " +
		"pass detail:'full' or attributes:[...] for exact schema details. Also returns supports, " +
		"variations, allowedBlocks, and relevant editor presets when supported.",
	parameters: Type.Object({
		block_name: Type.Optional(
			Type.String({
				description:
					'Fully-qualified block name, e.g. "core/heading", "core/paragraph", "core/image".',
			}),
		),
		name: Type.Optional(
			Type.String({
				description: "Alias for block_name. Prefer block_name in new calls.",
			}),
		),
		detail: Type.Optional(
			Type.Union([Type.Literal("compact"), Type.Literal("full")], {
				description: "Schema verbosity. Default compact; use full only when exact nested schema details are needed.",
			}),
		),
		attributes: Type.Optional(
			Type.Array(Type.String(), {
				description: "Limit full attribute schema output to these attribute names.",
			}),
		),
	}),
	execute: async (_id, params) => {
		const { attributes, detail, name } = params as {
			block_name?: string;
			name?: string;
			detail?: "compact" | "full";
			attributes?: string[];
		};
		const block_name = firstString((params as { block_name?: unknown }).block_name, name);
		if (!block_name) {
			throw new Error(
				'Missing required args for get_block_schema: block_name. Example: get_block_schema {"block_name":"core/heading"}.',
			);
		}

		const blockType = wp().blocks.getBlockType(block_name);
		if (!blockType) {
			const all = wp()
				.blocks.getBlockTypes()
				.map((t: any) => t.name)
				.sort();
			throw new Error(
				`Unknown block type: ${block_name}\nAvailable blocks: ${all.slice(0, 30).join(", ")}${all.length > 30 ? "..." : ""}`,
			);
		}

		const allAttributes = blockType.attributes || {};
		const selectedAttributes = Array.isArray(attributes) && attributes.length > 0
			? pickAttributes(allAttributes, attributes)
			: allAttributes;
		const exactAttributes = detail === "full" || (Array.isArray(attributes) && attributes.length > 0);
		const fullSchema = detail === "full";

		const result: any = {
			name: blockType.name,
			title: blockType.title,
			category: blockType.category,
		};
		if (!exactAttributes) {
			result.attributeCount = Object.keys(allAttributes).length;
			result.attributeNames = Object.keys(allAttributes).sort();
		}
		result.attributes = exactAttributes ? selectedAttributes : summarizeAttributes(allAttributes);
		result.supports = fullSchema ? blockType.supports || {} : summarizeSupports(blockType.supports || {});
		if (!fullSchema) {
			result.note = exactAttributes
				? "Exact requested attributes with compact supports/presets. Pass detail:'full' for the complete schema."
				: "Compact schema. Pass detail:'full' or attributes:[...] for exact attribute schemas.";
		}

		if (blockType.variations && blockType.variations.length > 0) {
			result.variations = blockType.variations.map((v: any) => ({
				name: v.name,
				title: v.title,
				description: v.description,
			}));
		}

		if (blockType.allowedBlocks && blockType.allowedBlocks.length > 0) {
			result.allowedBlocks = blockType.allowedBlocks;
		}

		// Include editor presets if the block supports color/typography
		const supports = blockType.supports || {};
		const settings = wp().data.select("core/block-editor").getSettings();
		const presets: any = {};

		if (supports.color) {
			if (settings.colors && settings.colors.length > 0) {
				presets.colors = settings.colors.map((c: any) => ({
					slug: c.slug,
					name: c.name,
					color: c.color,
				}));
			}
			if (settings.gradients && settings.gradients.length > 0) {
				presets.gradients = settings.gradients.map((g: any) => ({
					slug: g.slug,
					name: g.name,
				}));
			}
		}
		if (supports.typography) {
			if (settings.fontSizes && settings.fontSizes.length > 0) {
				presets.fontSizes = settings.fontSizes.map((f: any) => ({
					slug: f.slug,
					name: f.name,
					size: f.size,
				}));
			}
		}

		if (Object.keys(presets).length > 0 && fullSchema) {
			result.presets = presets;
		} else if (Object.keys(presets).length > 0) {
			result.presetCounts = Object.fromEntries(
				Object.entries(presets).map(([key, value]) => [key, Array.isArray(value) ? value.length : 0]),
			);
		}

		// Block-width align vs text alignment is a common trap: agents reach for
		// the align attribute to center text, which only controls block width.
		const hasTextAlignTarget = Boolean(
			allAttributes.textAlign ||
				(blockType.supports && blockType.supports.typography && blockType.supports.typography.textAlign),
		);
		if (hasTextAlignTarget) {
			result.textAlignment =
				"To align this block's TEXT (left, center, right), pass text_align to create_block/update_block. " +
				"The align attribute is block WIDTH (wide/full), not text alignment.";
		}

		return {
			content: [{ type: "text" as const, text: JSON.stringify(result) }],
			details: {
				block_name,
				detail: fullSchema ? "full" : exactAttributes ? "attributes" : "compact",
				attributeCount: Object.keys(allAttributes).length,
			},
		};
	},
};

function firstString(...values: unknown[]): string | undefined {
	for (const value of values) {
		if (typeof value === "string" && value.trim()) return value;
	}
	return undefined;
}

function pickAttributes(allAttributes: Record<string, unknown>, names: string[]): Record<string, unknown> {
	const picked: Record<string, unknown> = {};
	for (const name of names) {
		if (Object.prototype.hasOwnProperty.call(allAttributes, name)) picked[name] = allAttributes[name];
	}
	return picked;
}

function summarizeAttributes(allAttributes: Record<string, any>): Record<string, unknown> {
	const summary: Record<string, unknown> = {};
	for (const name of Object.keys(allAttributes).sort()) {
		const attribute = allAttributes[name];
		if (!attribute || typeof attribute !== "object") {
			summary[name] = attribute;
			continue;
		}
		const item: Record<string, unknown> = {};
		for (const key of ["type", "source", "selector", "attribute", "role", "default", "enum"] as const) {
			if (attribute[key] !== undefined) item[key] = attribute[key];
		}
		summary[name] = item;
	}
	return summary;
}

function summarizeSupports(supports: Record<string, any>): Record<string, unknown> {
	const summary: Record<string, unknown> = {};
	for (const key of Object.keys(supports).sort()) {
		const value = supports[key];
		if (!value || typeof value !== "object" || Array.isArray(value)) {
			summary[key] = value;
			continue;
		}
		summary[key] = Object.keys(value).sort();
	}
	return summary;
}
