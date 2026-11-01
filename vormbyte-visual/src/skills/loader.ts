/**
 * Skill loader — bundles all SKILL.md + references at build time via Vite glob imports.
 *
 * Directory convention (mirrors Claude Code):
 *   src/skills/<skill-name>/SKILL.md          — required, YAML frontmatter + body
 *   src/skills/<skill-name>/references/*.md   — optional, loaded on demand
 *
 * Frontmatter fields:
 *   name:        unique identifier
 *   description: shown in system prompt for triggering
 *   editor:      "shared" | "gutenberg" (default: "shared")
 */

// --- Vite glob imports (resolved at build time) ---

const skillFiles: Record<string, string> = import.meta.glob(
	"./**/SKILL.md",
	{ eager: true, query: "?raw", import: "default" },
) as any;

const referenceFiles: Record<string, string> = import.meta.glob(
	"./**/references/*.md",
	{ eager: true, query: "?raw", import: "default" },
) as any;

// --- Frontmatter parser ---

interface SkillMeta {
	name: string;
	description: string;
	editor: "shared" | "gutenberg";
}

function parseFrontmatter(raw: string): { meta: SkillMeta; body: string } {
	const match = raw.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/);
	if (!match) throw new Error("SKILL.md missing YAML frontmatter");

	const yamlBlock = match[1];
	const body = match[2];

	// Minimal YAML parser — handles simple key: value and multi-line >
	const meta: Record<string, string> = {};
	let currentKey = "";
	let currentValue = "";
	let multiLine = false;

	for (const line of yamlBlock.split("\n")) {
		if (multiLine) {
			if (line.startsWith("  ")) {
				currentValue += " " + line.trim();
				continue;
			} else {
				meta[currentKey] = currentValue.trim();
				multiLine = false;
			}
		}
		const kvMatch = line.match(/^(\w+):\s*(.*)$/);
		if (kvMatch) {
			currentKey = kvMatch[1];
			const val = kvMatch[2].trim();
			if (val === ">" || val === "|") {
				multiLine = true;
				currentValue = "";
			} else {
				meta[currentKey] = val;
			}
		}
	}
	if (multiLine) meta[currentKey] = currentValue.trim();

	return {
		meta: {
			name: meta.name || "unknown",
			description: meta.description || "",
			editor: (meta.editor as SkillMeta["editor"]) || "shared",
		},
		body,
	};
}

// --- Skill registry ---

export interface Skill {
	name: string;
	description: string;
	editor: "shared" | "gutenberg";
	body: string;
	/** Map of "references/foo.md" → content */
	references: Record<string, string>;
}

function buildRegistry(): Skill[] {
	const skills: Skill[] = [];

	for (const [path, raw] of Object.entries(skillFiles)) {
		// path looks like "./<skill-name>/SKILL.md"
		const dirMatch = path.match(/^\.\/([^/]+)\/SKILL\.md$/);
		if (!dirMatch) continue;
		const dirName = dirMatch[1];

		const { meta, body } = parseFrontmatter(raw);

		// Collect references for this skill
		const refs: Record<string, string> = {};
		const refPrefix = `./${dirName}/references/`;
		for (const [refPath, refRaw] of Object.entries(referenceFiles)) {
			if (refPath.startsWith(refPrefix)) {
				const refName = "references/" + refPath.slice(refPrefix.length);
				refs[refName] = refRaw;
			}
		}

		skills.push({
			name: meta.name,
			description: meta.description,
			editor: meta.editor,
			body,
			references: refs,
		});
	}

	return skills;
}

let _registry: Skill[] | null = null;

function getRegistry(): Skill[] {
	if (!_registry) _registry = buildRegistry();
	return _registry;
}

// --- Public API ---

/** Get skills applicable to the given editor. */
export function getSkills(editor: "gutenberg"): Skill[] {
	return getRegistry().filter(
		(s) => s.editor === "shared" || s.editor === editor,
	);
}

/** Get a specific skill by name. */
export function getSkill(name: string): Skill | undefined {
	return getRegistry().find((s) => s.name === name);
}

/** Build the system-prompt section listing available skills. */
export function skillsPromptSection(editor: "gutenberg"): string {
	const skills = getSkills(editor);
	if (skills.length === 0) return "";

	const lines = skills.map(
		(s) => `- **${s.name}**: ${s.description}`,
	);
	return `\n## Available Skills\n\nUse the **use_skill** tool to load a skill's full instructions when relevant.\n\n${lines.join("\n")}\n`;
}
