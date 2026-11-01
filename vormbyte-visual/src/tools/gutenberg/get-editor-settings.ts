import { Type } from "@sinclair/typebox";
import type { AgentTool } from "../../tool-types";
import { wp } from "./gutenberg-helpers";

export const getEditorSettingsTool: AgentTool = {
	name: "get_editor_settings",
	label: "Editor Settings",
	description:
		"Get Gutenberg editor settings: color palette, font sizes, gradients, and other theme presets. " +
		"Use this to discover available preset slugs for textColor, backgroundColor, fontSize, and gradient attributes.",
	parameters: Type.Object({}),
	execute: async () => {
		const settings = wp().data.select("core/block-editor").getSettings();
		const result: any = {};

		if (settings.colors && settings.colors.length > 0) {
			result.colors = settings.colors.map((c: any) => ({
				slug: c.slug,
				name: c.name,
				color: c.color,
			}));
		}

		if (settings.fontSizes && settings.fontSizes.length > 0) {
			result.fontSizes = settings.fontSizes.map((f: any) => ({
				slug: f.slug,
				name: f.name,
				size: f.size,
			}));
		}

		if (settings.gradients && settings.gradients.length > 0) {
			result.gradients = settings.gradients.map((g: any) => ({
				slug: g.slug,
				name: g.name,
				gradient: g.gradient,
			}));
		}

		if (settings.fontFamilies && settings.fontFamilies.length > 0) {
			result.fontFamilies = settings.fontFamilies.map((f: any) => ({
				slug: f.slug,
				name: f.name,
			}));
		}

		return {
			content: [{ type: "text" as const, text: JSON.stringify(result) }],
			details: {},
		};
	},
};
