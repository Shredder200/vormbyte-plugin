import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { wp } from "./gutenberg-helpers";

export const undoTool: AgentTool = {
	name: "undo",
	label: "Undo",
	description: "Undo the last change.",
	parameters: Type.Object({}),
	execute: async () => {
		wp().data.dispatch("core/editor").undo();
		return {
			content: [{ type: "text" as const, text: "Undone." }],
			details: {},
		};
	},
};

export const redoTool: AgentTool = {
	name: "redo",
	label: "Redo",
	description: "Redo the last undone change.",
	parameters: Type.Object({}),
	execute: async () => {
		wp().data.dispatch("core/editor").redo();
		return {
			content: [{ type: "text" as const, text: "Redone." }],
			details: {},
		};
	},
};

export const saveTool: AgentTool = {
	name: "save",
	label: "Save",
	description: "Save the current post/page.",
	parameters: Type.Object({}),
	execute: async () => {
		wp().data.dispatch("core/editor").savePost();
		return {
			content: [{ type: "text" as const, text: "Post saved." }],
			details: {},
		};
	},
};
