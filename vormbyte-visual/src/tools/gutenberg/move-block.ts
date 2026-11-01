import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { firstString, wp } from "./gutenberg-helpers";

export const moveBlockTool: AgentTool = {
	name: "move_block",
	label: "Move Block",
	description:
		"Move a block to a new parent or position. " +
		"Looks up the current parent automatically for the move operation.",
	parameters: Type.Object({
		client_id: Type.Optional(Type.String({
			description: "The block's clientId to move.",
		})),
		clientId: Type.Optional(Type.String({
			description: "Alias for client_id. Prefer client_id in new calls.",
		})),
		target_parent_client_id: Type.Optional(
			Type.String({
				description:
					"clientId of the new parent block. Omit to move to the document root.",
			}),
		),
		targetParentClientId: Type.Optional(Type.String({
			description: "Alias for target_parent_client_id. Prefer target_parent_client_id in new calls.",
		})),
		position: Type.Optional(
			Type.Number({
				description:
					"Position among siblings (0-based). Omit to append at end.",
			}),
		),
	}),
	execute: async (_id, params) => {
		const { clientId, targetParentClientId, position } = params as {
			client_id?: string;
			clientId?: string;
			target_parent_client_id?: string;
			targetParentClientId?: string;
			position?: number;
		};
		const client_id = firstString((params as { client_id?: unknown }).client_id, clientId);
		const target_parent_client_id = firstString((params as { target_parent_client_id?: unknown }).target_parent_client_id, targetParentClientId);
		if (!client_id) {
			throw new Error('Missing required args for move_block: client_id. Example: move_block {"client_id":"...","position":0}.');
		}

		const select = wp().data.select("core/block-editor");
		const dispatch = wp().data.dispatch("core/block-editor");

		const block = select.getBlock(client_id);
		if (!block) {
			throw new Error("Block not found: " + client_id);
		}

		if (target_parent_client_id) {
			const targetParent = select.getBlock(target_parent_client_id);
			if (!targetParent) {
				throw new Error("Target parent block not found: " + target_parent_client_id);
			}
		}

		// Find current parent
		const fromRoot = select.getBlockRootClientId(client_id);

		// Compute target index
		const toRoot = target_parent_client_id || "";
		let targetIndex;
		if (position !== null && position !== undefined) {
			targetIndex = position;
		} else {
			const siblings = toRoot
				? (select.getBlock(toRoot) || {}).innerBlocks || []
				: select.getBlocks();
			targetIndex = siblings.length;
		}

		dispatch.moveBlockToPosition(client_id, fromRoot, toRoot, targetIndex);

		return {
			content: [
				{
					type: "text" as const,
					text: `Moved block ${client_id} into ${toRoot || "(root)"}${targetIndex !== undefined ? ` at position ${targetIndex}` : ""}.`,
				},
			],
			details: {
				success: true,
				movedId: client_id,
				newParentId: toRoot || "(root)",
				position: targetIndex,
			},
		};
	},
};
