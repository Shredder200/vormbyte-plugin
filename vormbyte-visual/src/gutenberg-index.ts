import { exposeGutenbergTools } from "./gutenberg-tools";

declare const wp: {
	domReady: (callback: () => void) => void;
	data: { select: (store: string) => Record<string, unknown> };
};

/**
 * Wait for the Gutenberg block editor to be ready.
 * Uses wp.domReady if available, otherwise polls for the block editor store.
 */
function waitForGutenberg(callback: () => void): void {
	if (typeof wp !== "undefined" && wp.domReady) {
		wp.domReady(() => {
			// Extra poll to ensure block-editor store is initialized.
			let attempts = 0;
			const poll = setInterval(() => {
				try {
					const store = wp.data.select("core/block-editor");
					if (store || ++attempts > 40) {
						clearInterval(poll);
						if (store) callback();
					}
				} catch {
					if (++attempts > 40) clearInterval(poll);
				}
			}, 250);
		});
		return;
	}

	let attempts = 0;
	const poll = setInterval(() => {
		try {
			if (
				typeof wp !== "undefined" &&
				wp.data &&
				wp.data.select("core/block-editor")
			) {
				clearInterval(poll);
				callback();
			} else if (++attempts > 40) {
				clearInterval(poll);
			}
		} catch {
			if (++attempts > 40) clearInterval(poll);
		}
	}, 500);
}

waitForGutenberg(exposeGutenbergTools);
