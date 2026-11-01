import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { firstString, wp } from "./gutenberg-helpers";

export const getBlockAttributesTool: AgentTool = {
	name: "get_block_attributes",
	label: "Block Attributes",
	description:
		"Get the current attributes of a specific block. Schema is omitted by default for token efficiency; " +
		"use include_schema when exact schema is needed, or get_block_schema with attributes:[...].",
	parameters: Type.Object({
		client_id: Type.Optional(
			Type.String({
				description: "The block's clientId to inspect.",
			}),
		),
		clientId: Type.Optional(
			Type.String({
				description: "Alias for client_id. Prefer client_id in new calls.",
			}),
		),
		include_schema: Type.Optional(Type.Boolean({
			description: "Include the full block attribute schema. Default false.",
		})),
	}),
	execute: async (_id, params) => {
		const { clientId, include_schema } = params as { clientId?: string; include_schema?: boolean };
		const client_id = firstString((params as { client_id?: unknown }).client_id, clientId);
		if (!client_id) {
			throw new Error('Missing required args for get_block_attributes: client_id. Example: get_block_attributes {"client_id":"..."}.');
		}

		const block = wp().data.select("core/block-editor").getBlock(client_id);
		if (!block) {
			throw new Error("Block not found: " + client_id);
		}

		const result: Record<string, unknown> = {
			clientId: block.clientId,
			name: block.name,
			attributes: block.attributes,
		};
		if (include_schema === true) {
			const blockType = wp().blocks.getBlockType(block.name);
			result.schema = blockType ? blockType.attributes : {};
		} else {
			result.schemaHint = "Omitted. Use include_schema:true or get_block_schema with attributes:[...] when needed.";
		}

		return {
			content: [{ type: "text" as const, text: JSON.stringify(result) }],
			details: { client_id },
		};
	},
};
