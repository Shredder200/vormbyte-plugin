import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { firstString, wp } from "./gutenberg-helpers";

export const deleteBlockTool: AgentTool = {
	name: "delete_block",
	label: "Delete Block",
	description: "Delete a block from the page.",
	parameters: Type.Object({
		client_id: Type.Optional(
			Type.String({
				description: "The block's clientId to delete.",
			}),
		),
		clientId: Type.Optional(
			Type.String({
				description: "Alias for client_id. Prefer client_id in new calls.",
			}),
		),
	}),
	execute: async (_id, params) => {
		const { clientId } = params as { clientId?: string };
		const client_id = firstString((params as { client_id?: unknown }).client_id, clientId);
		if (!client_id) {
			throw new Error('Missing required args for delete_block: client_id. Example: delete_block {"client_id":"..."}.');
		}

		const block = wp().data.select("core/block-editor").getBlock(client_id);
		if (!block) {
			throw new Error("Block not found: " + client_id);
		}

		const info = {
			clientId: block.clientId,
			name: block.name,
		};

		wp().data.dispatch("core/block-editor").removeBlock(client_id);

		return {
			content: [
				{
					type: "text" as const,
					text: `Deleted ${info.name} block "${info.clientId}".`,
				},
			],
			details: { success: true, deleted: info },
		};
	},
};
