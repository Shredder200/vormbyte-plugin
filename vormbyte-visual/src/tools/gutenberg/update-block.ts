import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { firstString, wp, validateAttributes, resolveTextAlign } from "./gutenberg-helpers";

export const updateBlockTool: AgentTool = {
	name: "update_block",
	label: "Update Block",
	description:
		"Update attributes on an existing block. Validates all attributes against the block schema. " +
		"Reads back the block to confirm changes were applied.",
	parameters: Type.Object({
		client_id: Type.Optional(Type.String({
			description: "The block's clientId to update.",
		})),
		clientId: Type.Optional(Type.String({
			description: "Alias for client_id. Prefer client_id in new calls.",
		})),
		attributes: Type.Record(Type.String(), Type.Unknown(), {
			description:
				"Attributes to update. Use get_block_schema to discover available attributes. " +
				"For preset colors use slug strings (e.g. textColor: \"contrast\"). " +
				"For inline styles use style object (e.g. style: { color: { text: \"#111\" } }).",
		}),
		text_align: Type.Optional(
			Type.String({
				description:
					'Text alignment "left", "center" or "right". The tool stores it wherever this block keeps text alignment. ' +
					"Do NOT use the align attribute for this: align is block width (wide/full).",
			}),
		),
	}),
	execute: async (_id, params) => {
		const { clientId, attributes, text_align } = params as {
			client_id?: string;
			clientId?: string;
			attributes: Record<string, unknown>;
			text_align?: string;
		};
		const client_id = firstString((params as { client_id?: unknown }).client_id, clientId);
		if (!client_id) {
			throw new Error('Missing required args for update_block: client_id. Example: update_block {"client_id":"...","attributes":{}}.');
		}

		const block = wp().data.select("core/block-editor").getBlock(client_id);
		if (!block) {
			throw new Error("Block not found: " + client_id);
		}

		// Route text alignment to where this block stores it, merging onto the
		// block's existing style so color/fontSize survive.
		const { attrs: safeAttrs, warning } = resolveTextAlign(
			block.name,
			attributes,
			text_align,
			block.attributes && block.attributes.style,
		);

		// Validate attributes
		const errors = validateAttributes(block.name, safeAttrs);
		if (errors.length > 0) {
			const msg = errors
				.map(
					(e: any) =>
						`  - ${e.key}: ${e.message}${e.validOptions ? ` Valid: [${e.validOptions.join(", ")}]` : ""}`,
				)
				.join("\n");
			throw new Error(`Invalid attributes:\n${msg}`);
		}

		// Apply
		wp().data.dispatch("core/block-editor").updateBlockAttributes(client_id, safeAttrs);

		// Read back
		const updated = wp().data.select("core/block-editor").getBlock(client_id);
		const confirmed: Record<string, any> = {};
		const keys = Object.keys(safeAttrs);
		for (let i = 0; i < keys.length; i++) {
			confirmed[keys[i]] = updated.attributes[keys[i]];
		}

		const noteText = warning ? `\nNote: ${warning}` : "";

		return {
			content: [
				{
					type: "text" as const,
					text: `Updated ${Object.keys(safeAttrs).length} attribute(s) on block ${client_id}.${noteText}`,
				},
			],
			details: {
				success: true,
				clientId: client_id,
				applied: confirmed,
				...(warning ? { warning } : {}),
			},
		};
	},
};
