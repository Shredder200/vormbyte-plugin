import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { firstString, normalizeBlockSpec, wp, validateBlockType, validateAttributes, resolveTextAlign } from "./gutenberg-helpers";

function formatValidationErrors(errors: any[]): string {
	return errors
		.map(
			(e: any) =>
				`  - ${e.key}: ${e.message}${e.validOptions ? ` Valid: [${e.validOptions.join(", ")}]` : ""}`,
		)
		.join("\n");
}

function buildInnerBlocks(
	specs: any[],
	warnings: string[],
): any[] {
	const result: any[] = [];
	for (let i = 0; i < specs.length; i++) {
		const spec = normalizeBlockSpec(specs[i]);
		const typeErr = validateBlockType(spec.block_name);
		if (typeErr) {
			throw new Error(
				`${typeErr.error}\nAvailable: ${typeErr.available.slice(0, 30).join(", ")}${typeErr.available.length > 30 ? "..." : ""}`,
			);
		}
		const { attrs: innerAttrs, warning } = resolveTextAlign(spec.block_name, spec.attributes || {});
		if (warning) warnings.push(warning);
		const innerErrors = validateAttributes(spec.block_name, innerAttrs);
		if (innerErrors.length > 0) {
			throw new Error(
				`Invalid attributes for ${spec.block_name}:\n${formatValidationErrors(innerErrors)}`,
			);
		}
		const children = spec.inner_blocks
			? buildInnerBlocks(spec.inner_blocks, warnings)
			: [];
		result.push(
			wp().blocks.createBlock(
				spec.block_name,
				innerAttrs,
				children,
			),
		);
	}
	return result;
}

export const createBlockTool: AgentTool = {
	name: "create_block",
	label: "Create Block",
	description:
		"Create a new block in the page. Validates the block type and all attributes " +
		"including preset slugs. Supports nested inner_blocks for container blocks " +
		"(e.g. core/columns with core/column children). " +
		"Returns the new block's clientId. " +
		"Use the most specific native block and its built-in supports/preset slugs; avoid core/html " +
		"and inline style unless no native option fits — output must stay editable in the block editor.",
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
		parent_client_id: Type.Optional(
			Type.String({
				description:
					"clientId of the parent block to insert into. Omit to add at the document root.",
			}),
		),
		position: Type.Optional(
			Type.Number({
				description:
					"Insert position among siblings (0-based). Omit to append at end.",
			}),
		),
		attributes: Type.Optional(
			Type.Record(Type.String(), Type.Unknown(), {
				description:
					"Initial attribute values. Use get_block_schema to discover available attributes.",
			}),
		),
		text_align: Type.Optional(
			Type.String({
				description:
					'Text alignment "left", "center" or "right". The tool stores it wherever this block keeps text alignment. ' +
					"Do NOT use the align attribute for this: align is block width (wide/full).",
			}),
		),
		inner_blocks: Type.Optional(
			Type.Array(
				Type.Object({
					block_name: Type.Optional(Type.String()),
					name: Type.Optional(Type.String()),
					attributes: Type.Optional(Type.Record(Type.String(), Type.Unknown())),
					inner_blocks: Type.Optional(Type.Array(Type.Unknown())),
					innerBlocks: Type.Optional(Type.Array(Type.Unknown())),
				}),
				{
					description:
						"Nested inner blocks to create inside this block. " +
						"Each entry has block_name, optional attributes, and optional inner_blocks.",
				},
			),
		),
	}),
	execute: async (_id, params) => {
		const { name, parent_client_id, position, attributes, text_align, inner_blocks, innerBlocks } =
			params as {
				block_name?: string;
				name?: string;
				parent_client_id?: string;
				position?: number;
				attributes?: Record<string, unknown>;
				text_align?: string;
				inner_blocks?: Array<{
					block_name: string;
					attributes?: Record<string, unknown>;
					inner_blocks?: unknown[];
				}>;
				innerBlocks?: unknown[];
			};
		const block_name = firstString((params as { block_name?: unknown }).block_name, name);
		if (!block_name) {
			throw new Error('Missing required args for create_block: block_name. Example: create_block {"block_name":"core/paragraph"}.');
		}

		// Validate block type
		const typeErr = validateBlockType(block_name);
		if (typeErr) {
			throw new Error(
				`${typeErr.error}\nAvailable: ${typeErr.available.slice(0, 30).join(", ")}${typeErr.available.length > 30 ? "..." : ""}`,
			);
		}

		// Route text alignment (text_align, or a stray align:left|center|right) to
		// wherever this block actually stores it, before validating.
		const warnings: string[] = [];
		const { attrs: safeAttrs, warning } = resolveTextAlign(block_name, attributes || {}, text_align);
		if (warning) warnings.push(warning);

		// Validate attributes
		const attrErrors = validateAttributes(block_name, safeAttrs);
		if (attrErrors.length > 0) {
			throw new Error(
				`Invalid attributes:\n${formatValidationErrors(attrErrors)}`,
			);
		}

		// Recursively build inner blocks
		const childSpecs = Array.isArray(inner_blocks) ? inner_blocks : Array.isArray(innerBlocks) ? innerBlocks : [];
		const children = buildInnerBlocks(childSpecs, warnings);

		// Create the block
		const block = wp().blocks.createBlock(block_name, safeAttrs, children);

		// Insert it
		const dispatch = wp().data.dispatch("core/block-editor");
		if (parent_client_id) {
			const parentBlock = wp()
				.data.select("core/block-editor")
				.getBlock(parent_client_id);
			if (!parentBlock) {
				throw new Error("Parent block not found: " + parent_client_id);
			}
			const innerCount = parentBlock.innerBlocks.length;
			const insertAt = position !== undefined && position !== null ? position : innerCount;
			dispatch.insertBlocks([block], insertAt, parent_client_id);
		} else {
			const allBlocks = wp().data.select("core/block-editor").getBlocks();
			const insertAt = position !== undefined && position !== null ? position : allBlocks.length;
			dispatch.insertBlocks([block], insertAt);
		}

		const noteText = warnings.length > 0 ? `\nNote:\n${warnings.map((w) => `  - ${w}`).join("\n")}` : "";

		return {
			content: [
				{
					type: "text" as const,
					text: `Created ${block_name} block with clientId: ${block.clientId}${noteText}`,
				},
			],
			details: {
				success: true,
				clientId: block.clientId,
				name: block_name,
				...(warnings.length > 0 ? { warnings } : {}),
			},
		};
	},
};
