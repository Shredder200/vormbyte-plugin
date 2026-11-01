/**
 * Shared helpers for Gutenberg tool implementations.
 *
 * These are real functions that access wp.* globals at runtime
 * via the `window` object (the bundle runs in the editor page).
 */

const _wp = () => (window as any).wp;

export { _wp as wp };

export function firstString(...values: unknown[]): string | undefined {
	for (const value of values) {
		if (typeof value === "string" && value.trim()) return value;
	}
	return undefined;
}

export function normalizeBlockSpec(spec: any): any {
	const blockName = firstString(spec?.block_name, spec?.name);
	return {
		block_name: blockName,
		attributes: spec?.attributes,
		inner_blocks: Array.isArray(spec?.inner_blocks)
			? spec.inner_blocks.map(normalizeBlockSpec)
			: Array.isArray(spec?.innerBlocks)
				? spec.innerBlocks.map(normalizeBlockSpec)
				: undefined,
	};
}

export function validateBlockType(blockName: string) {
	const type = _wp().blocks.getBlockType(blockName);
	if (type) return null;
	const all = _wp()
		.blocks.getBlockTypes()
		.map((t: any) => t.name)
		.sort();
	return {
		error: "Unknown block type: " + blockName,
		available: all,
	};
}

const TEXT_ALIGN_VALUES = ["left", "center", "right"];

/**
 * Headings, paragraphs and similar text blocks align their TEXT through a
 * `textAlign` attribute (or `style.typography.textAlign`), while `align` is
 * reserved for block WIDTH (wide/full). Agents routinely pass `align: "center"`
 * meaning "center the text", which silently does nothing. This routes an
 * explicit `text_align`, and re-homes a stray `align: left|center|right`, into
 * wherever the block actually stores text alignment. Blocks with no text-align
 * target (e.g. core/image, where left/center/right IS block alignment) are left
 * untouched. Returns the (copied) attributes and a warning when it corrected one.
 */
export function resolveTextAlign(
	blockName: string,
	attrs: Record<string, any>,
	explicit?: string,
	currentStyle?: Record<string, any>,
): { attrs: Record<string, any>; warning?: string } {
	const type = _wp().blocks.getBlockType(blockName);
	if (!type) return { attrs };

	const hasTextAlignAttr = Boolean(type.attributes && type.attributes.textAlign);
	const supportsTypographyTextAlign = Boolean(
		type.supports && type.supports.typography && type.supports.typography.textAlign,
	);
	const target: "attr" | "style" | null = hasTextAlignAttr
		? "attr"
		: supportsTypographyTextAlign
			? "style"
			: null;

	const next: Record<string, any> = { ...attrs };
	const apply = (value: string): void => {
		if (target === "attr") {
			next.textAlign = value;
		} else if (target === "style") {
			// Merge onto the block's existing style (and any style passed in this
			// call) so writing textAlign never drops color/fontSize/etc.
			const base = currentStyle && typeof currentStyle === "object" ? currentStyle : {};
			const passed = next.style && typeof next.style === "object" ? next.style : {};
			const style: Record<string, any> = { ...base, ...passed };
			const typography =
				style.typography && typeof style.typography === "object" ? { ...style.typography } : {};
			typography.textAlign = value;
			style.typography = typography;
			next.style = style;
		}
	};

	let warning: string | undefined;

	const align = next.align;
	if (typeof align === "string" && TEXT_ALIGN_VALUES.includes(align) && target) {
		apply(align);
		delete next.align;
		warning =
			`Applied text alignment "${align}" to ${blockName}. On this block "align" controls block width ` +
			`(wide/full), not text, so left/center/right was routed to text alignment. Use text_align next time.`;
	}

	if (typeof explicit === "string" && TEXT_ALIGN_VALUES.includes(explicit)) {
		if (target) {
			apply(explicit);
			delete next.align;
		} else {
			warning = `${blockName} has no text alignment, so text_align "${explicit}" was ignored.`;
		}
	}

	return { attrs: next, warning };
}

export function validateAttributes(
	blockName: string,
	attrs: Record<string, any>,
) {
	const type = _wp().blocks.getBlockType(blockName);
	if (!type)
		return [{ key: "_block", message: "Unknown block type: " + blockName }];
	const schema = type.attributes || {};
	const errors: any[] = [];
	const keys = Object.keys(attrs);

	// Get editor settings for preset validation
	const editorSettings = _wp()
		.data.select("core/block-editor")
		.getSettings();

	for (let i = 0; i < keys.length; i++) {
		const key = keys[i];
		const value = attrs[key];

		// style is a free-form nested object, allow it
		if (key === "style") continue;

		// Check if key exists in schema
		if (!schema[key]) {
			const allKeys = Object.keys(schema);
			const close = allKeys.filter(
				(k) => k.indexOf(key) !== -1 || key.indexOf(k) !== -1,
			);
			errors.push({
				key,
				message:
					'Unknown attribute "' +
					key +
					'".' +
					(close.length
						? " Did you mean: " + close.join(", ") + "?"
						: " Available: " + allKeys.join(", ")),
			});
			continue;
		}

		const attrSchema = schema[key];

		// Type check
		if (attrSchema.type && value !== undefined && value !== null) {
			const expectedType = attrSchema.type;
			const actualType = Array.isArray(value) ? "array" : typeof value;
			if (expectedType === "integer" || expectedType === "number") {
				if (typeof value !== "number") {
					errors.push({
						key,
						message:
							'"' + key + '" must be a number, got ' + actualType + ".",
					});
				}
			} else if (expectedType === "boolean") {
				if (typeof value !== "boolean") {
					errors.push({
						key,
						message:
							'"' + key + '" must be a boolean, got ' + actualType + ".",
					});
				}
			} else if (expectedType === "string") {
				if (typeof value !== "string") {
					errors.push({
						key,
						message:
							'"' + key + '" must be a string, got ' + actualType + ".",
					});
				}
			} else if (expectedType === "array") {
				if (!Array.isArray(value)) {
					errors.push({
						key,
						message:
							'"' + key + '" must be an array, got ' + actualType + ".",
					});
				}
			} else if (expectedType === "object") {
				if (typeof value !== "object" || Array.isArray(value)) {
					errors.push({
						key,
						message:
							'"' + key + '" must be an object, got ' + actualType + ".",
					});
				}
			}
		}

		// Enum validation
		if (attrSchema.enum && value !== undefined && value !== null) {
			if (attrSchema.enum.indexOf(value) === -1) {
				errors.push({
					key,
					message: '"' + value + '" is not valid for "' + key + '".',
					validOptions: attrSchema.enum,
				});
			}
		}

		// Heading level: enforce 1-6
		if (blockName === "core/heading" && key === "level") {
			if (typeof value !== "number" || value < 1 || value > 6) {
				errors.push({
					key,
					message: "Heading level must be 1-6, got " + value + ".",
				});
			}
		}

		// Preset slug validation
		if (key === "textColor" || key === "backgroundColor") {
			const colors = editorSettings.colors || [];
			const slugs = colors.map((c: any) => c.slug);
			if (slugs.length > 0 && slugs.indexOf(value) === -1) {
				errors.push({
					key,
					message: '"' + value + '" is not a valid color preset slug.',
					validOptions: slugs,
				});
			}
		}
		if (key === "fontSize") {
			const fontSizes = editorSettings.fontSizes || [];
			const fSlugs = fontSizes.map((f: any) => f.slug);
			if (fSlugs.length > 0 && fSlugs.indexOf(value) === -1) {
				errors.push({
					key,
					message: '"' + value + '" is not a valid font size preset slug.',
					validOptions: fSlugs,
				});
			}
		}
		if (key === "gradient") {
			const gradients = editorSettings.gradients || [];
			const gSlugs = gradients.map((g: any) => g.slug);
			if (gSlugs.length > 0 && gSlugs.indexOf(value) === -1) {
				errors.push({
					key,
					message: '"' + value + '" is not a valid gradient preset slug.',
					validOptions: gSlugs,
				});
			}
		}
	}

	return errors;
}

export function validateInnerBlockTypes(
	parentName: string,
	childNames: string[],
) {
	const parentType = _wp().blocks.getBlockType(parentName);
	if (!parentType)
		return [
			{ key: "_parent", message: "Unknown parent block: " + parentName },
		];
	const allowed = parentType.allowedBlocks;
	if (!allowed || allowed.length === 0) return [];
	const errors: any[] = [];
	for (let i = 0; i < childNames.length; i++) {
		if (allowed.indexOf(childNames[i]) === -1) {
			errors.push({
				key: childNames[i],
				message:
					'"' +
					childNames[i] +
					'" is not allowed inside "' +
					parentName +
					'".',
				validOptions: allowed,
			});
		}
	}
	return errors;
}

export function walkBlocks(
	blocks: any[],
	maxDepth: number,
	currentDepth: number = 0,
	opts: { includeText?: boolean } = {},
): any[] {
	const result: any[] = [];
	for (let i = 0; i < blocks.length; i++) {
		const block = blocks[i];
		const info: any = { clientId: block.clientId, name: block.name };
		if (opts.includeText) {
			const text = getBlockTextPreview(block);
			if (text) info.text = text;
		}
		if (block.innerBlocks && block.innerBlocks.length > 0) {
			if (currentDepth >= maxDepth) {
				info.innerBlocks_count = block.innerBlocks.length;
				if (opts.includeText) {
					const descendantText = getDescendantTextPreview(block);
					if (descendantText) info.descendantText = descendantText;
				}
			} else {
				info.innerBlocks = walkBlocks(
					block.innerBlocks,
					maxDepth,
					currentDepth + 1,
					opts,
				);
			}
		}
		result.push(info);
	}
	return result;
}

export function getBlockTextPreview(block: any, limit = 160): string {
	const selector = _wp()?.data?.select?.("core/block-editor");
	const selectedAttrs = typeof selector?.getBlockAttributes === "function" && block?.clientId
		? selector.getBlockAttributes(block.clientId)
		: null;
	const attrs = { ...(block?.attributes || {}), ...(selectedAttrs || {}) };
	const pieces: string[] = [];
	for (const key of ["content", "text", "caption", "citation", "value", "alt"] as const) {
		const text = normalizePreviewText(attrs[key]);
		if (text) pieces.push(text);
	}
	for (const value of [block?.originalContent, block?.innerHTML]) {
		const text = normalizePreviewText(value);
		if (text) pieces.push(text);
	}
	return truncatePreview(pieces.join(" "), limit);
}

function getDescendantTextPreview(block: any, limit = 220): string {
	const pieces: string[] = [];
	collectDescendantText(block?.innerBlocks || [], pieces, limit);
	return truncatePreview(pieces.join(" "), limit);
}

function collectDescendantText(blocks: any[], pieces: string[], limit: number): void {
	for (const block of blocks) {
		const text = getBlockTextPreview(block, limit);
		if (text) pieces.push(text);
		if (pieces.join(" ").length >= limit) return;
		if (block?.innerBlocks?.length) collectDescendantText(block.innerBlocks, pieces, limit);
		if (pieces.join(" ").length >= limit) return;
	}
}

function normalizePreviewText(value: unknown): string {
	if (typeof value !== "string") return "";
	return value
		.replace(/<[^>]*>/g, " ")
		.replace(/\s+/g, " ")
		.trim();
}

function truncatePreview(value: string, limit: number): string {
	if (value.length <= limit) return value;
	return `${value.slice(0, Math.max(0, limit - 1)).trimEnd()}...`;
}
