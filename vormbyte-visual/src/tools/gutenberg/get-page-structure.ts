import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { wp, walkBlocks } from "./gutenberg-helpers";

export const getPageStructureTool: AgentTool = {
	name: "get_page_structure",
	label: "Page Structure",
	description:
		"Get the page block tree showing clientIds and block names. " +
		"Returns a compact outline — use get_block_attributes to inspect a specific block's values. " +
		"Use client_id to scope to a subtree. Use depth to control recursion (default 3). " +
		"Set include_text when locating blocks by visible copy; collapsed containers include a short descendant text preview.",
	parameters: Type.Object({
		client_id: Type.Optional(
			Type.String({
				description:
					"Scope to a subtree rooted at this block. Omit for the full page.",
			}),
		),
		depth: Type.Optional(
			Type.Number({
				description:
					"Max depth to recurse (default 3). Inner blocks beyond this depth are shown as innerBlocks_count.",
			}),
		),
		include_text: Type.Optional(
			Type.Boolean({
				description:
					"Include short visible text previews for locating/verifying copy. Default false.",
			}),
		),
		includeText: Type.Optional(
			Type.Boolean({
				description:
					"Alias for include_text for clients that use camelCase.",
			}),
		),
	}),
	execute: async (_id, params) => {
		const { client_id, depth, include_text, includeText } = params as {
			client_id?: string;
			depth?: number;
			include_text?: boolean;
			includeText?: boolean;
		};

		const maxDepth = depth !== undefined ? depth : 3;
		const includeTextResolved = include_text === true || includeText === true;
		const select = wp().data.select("core/block-editor");

		let blocks;
		if (client_id) {
			const block = select.getBlock(client_id);
			if (!block) {
				throw new Error("Block not found: " + client_id);
			}
			// Return the block itself as a single-element array
			blocks = [block];
		} else {
			blocks = select.getBlocks();
		}

		const tree = walkBlocks(blocks, maxDepth, 0, { includeText: includeTextResolved });

		return {
			content: [{ type: "text" as const, text: JSON.stringify(tree) }],
			details: { client_id: client_id || "document", depth: maxDepth, include_text: includeTextResolved },
		};
	},
};
