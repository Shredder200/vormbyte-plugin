import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { firstString, normalizeBlockSpec, wp, validateBlockType, validateAttributes } from "./gutenberg-helpers";

function formatValidationErrors(errors: any[]): string {
	return errors
		.map(
			(e: any) =>
				`  - ${e.key}: ${e.message}${e.validOptions ? ` Valid: [${e.validOptions.join(", ")}]` : ""}`,
		)
		.join("\n");
}

function buildInnerBlocks(specs: any[]): any[] {
	const result: any[] = [];
	for (let i = 0; i < specs.length; i++) {
		const spec = normalizeBlockSpec(specs[i]);
		const typeErr = validateBlockType(spec.block_name);
		if (typeErr) {
			throw new Error(
				`${typeErr.error}\nAvailable: ${typeErr.available.slice(0, 30).join(", ")}${typeErr.available.length > 30 ? "..." : ""}`,
			);
		}
		if (spec.attributes) {
			const errors = validateAttributes(spec.block_name, spec.attributes);
			if (errors.length > 0) {
				throw new Error(
					`Invalid attributes for ${spec.block_name}:\n${formatValidationErrors(errors)}`,
				);
			}
		}
		const children = spec.inner_blocks
			? buildInnerBlocks(spec.inner_blocks)
			: [];
		result.push(
			wp().blocks.createBlock(
				spec.block_name,
				spec.attributes || {},
				children,
			),
		);
	}
	return result;
}

export const replaceInnerBlocksTool: AgentTool = {
	name: "replace_inner_blocks",
	label: "Replace Inner Blocks",
	description:
		"Replace all children of a container block with new blocks. " +
		"Validates all nested block types and attributes. " +
		"Useful for rewriting the content of a core/group, core/columns, etc.",
	parameters: Type.Object({
		client_id: Type.Optional(
			Type.String({
				description: "clientId of the parent block whose children to replace.",
			}),
		),
		clientId: Type.Optional(
			Type.String({
				description: "Alias for client_id. Prefer client_id in new calls.",
			}),
		),
		inner_blocks: Type.Optional(Type.Array(
			Type.Object({
				block_name: Type.Optional(Type.String()),
				name: Type.Optional(Type.String()),
				attributes: Type.Optional(Type.Record(Type.String(), Type.Unknown())),
				inner_blocks: Type.Optional(Type.Array(Type.Unknown())),
				innerBlocks: Type.Optional(Type.Array(Type.Unknown())),
			}),
			{
				description:
					"Array of new child blocks. Each entry has block_name, optional attributes, and optional inner_blocks.",
			},
		)),
		innerBlocks: Type.Optional(Type.Array(Type.Unknown(), {
			description: "Alias for inner_blocks. Prefer inner_blocks in new calls.",
		})),
	}),
	execute: async (_id, params) => {
		const { clientId, inner_blocks, innerBlocks } = params as {
			client_id?: string;
			clientId?: string;
			inner_blocks: Array<{
				block_name: string;
				attributes?: Record<string, unknown>;
				inner_blocks?: unknown[];
			}>;
			innerBlocks?: unknown[];
		};
		const client_id = firstString((params as { client_id?: unknown }).client_id, clientId);
		if (!client_id) {
			throw new Error('Missing required args for replace_inner_blocks: client_id. Example: replace_inner_blocks {"client_id":"...","inner_blocks":[]}.');
		}
		const childSpecs = Array.isArray(inner_blocks) ? inner_blocks : Array.isArray(innerBlocks) ? innerBlocks : null;
		if (!childSpecs) {
			throw new Error('Missing required args for replace_inner_blocks: inner_blocks. Example: replace_inner_blocks {"client_id":"...","inner_blocks":[]}.');
		}

		const parentBlock = wp().data.select("core/block-editor").getBlock(client_id);
		if (!parentBlock) {
			throw new Error("Block not found: " + client_id);
		}

		const blocks = buildInnerBlocks(childSpecs);

		wp().data.dispatch("core/block-editor").replaceInnerBlocks(client_id, blocks);

		return {
			content: [
				{
					type: "text" as const,
					text: `Replaced inner blocks of ${client_id} with ${blocks.length} new block(s).`,
				},
			],
			details: {
				success: true,
				clientId: client_id,
				childCount: blocks.length,
			},
		};
	},
};
