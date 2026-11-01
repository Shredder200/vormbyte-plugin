import { Type } from "@sinclair/typebox";
import type { AgentTool, AgentToolResult } from "../tool-types";
import { getSkill, getSkills } from "../skills/loader";

function textResult(text: string, details: Record<string, unknown> = {}): AgentToolResult<Record<string, unknown>> {
	return { content: [{ type: "text" as const, text }], details };
}

/**
 * Tool that loads a skill's full instructions (SKILL.md body or a reference file).
 * Mirrors Claude Code's Skill tool: progressive disclosure — the system prompt
 * lists skill names + descriptions, this tool loads the full content on demand.
 */
export function makeUseSkillTool(editor: "gutenberg"): AgentTool {
	return {
		name: "use_skill",
		label: "Use Skill",
		description:
			"Load a skill's full instructions. Call with just the skill name to get the main " +
			"SKILL.md body. Pass a file path (e.g. 'references/api.md') to load a specific " +
			"reference file. Call with no arguments to list available skills.",
		parameters: Type.Object({
			name: Type.Optional(
				Type.String({ description: "Skill name to load. Omit to list available skills." }),
			),
			file: Type.Optional(
				Type.String({
					description:
						'Reference file path within the skill, e.g. "references/api.md". ' +
						"Omit to load the main SKILL.md body.",
				}),
			),
		}),
		execute: async (_toolCallId, params) => {
			const { name, file } = params as { name?: string; file?: string };

			// List mode
			if (!name) {
				const skills = getSkills(editor);
				if (skills.length === 0) return textResult("No skills available.");
				const list = skills.map((s) => {
					const refs = Object.keys(s.references);
					const refInfo = refs.length > 0 ? ` (references: ${refs.join(", ")})` : "";
					return `- **${s.name}**: ${s.description}${refInfo}`;
				});
				return textResult(list.join("\n"));
			}

			// Load specific skill
			const skill = getSkill(name);
			if (!skill) {
				const available = getSkills(editor).map((s) => s.name);
				return textResult(
					`Skill "${name}" not found. Available: ${available.join(", ") || "none"}`,
				);
			}

			// Check editor compatibility
			if (skill.editor !== "shared" && skill.editor !== editor) {
				return textResult(
					`Skill "${name}" is for ${skill.editor} only (current editor: ${editor}).`,
				);
			}

			// Load reference file
			if (file) {
				const content = skill.references[file];
				if (!content) {
					const available = Object.keys(skill.references);
					return textResult(
						`File "${file}" not found in skill "${name}". Available: ${available.join(", ") || "none"}`,
					);
				}
				return textResult(content, { skill: name, file });
			}

			// Load main body
			const refs = Object.keys(skill.references);
			const refHint =
				refs.length > 0
					? `\n\n---\nThis skill has reference files: ${refs.join(", ")}. Use use_skill(name: "${name}", file: "...") to load them.`
					: "";

			return textResult(skill.body + refHint, { skill: name });
		},
	};
}
