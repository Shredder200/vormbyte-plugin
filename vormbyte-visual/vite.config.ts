import { defineConfig } from "vite";

const entry = process.env.ENTRY || "gutenberg";

const entries: Record<string, { entry: string; fileName: string; name: string }> = {
  gutenberg: {
    entry: "src/gutenberg-index.ts",
    fileName: "gutenberg.js",
    name: "VisualGutenberg",
  },
  workspace: {
    entry: "src/workspace-index.ts",
    fileName: "workspace.js",
    name: "VisualWorkspace",
  },
};

const config = entries[entry];
if (!config) {
  throw new Error(`Unknown ENTRY="${entry}". Valid: ${Object.keys(entries).join(", ")}`);
}

export default defineConfig({
  plugins: [
    // Inject CSS into the JS bundle so only one file needs WP-enqueuing.
    {
      name: "inject-css",
      apply: "build",
      enforce: "post",
      generateBundle(_opts, bundle) {
        const cssChunk = Object.values(bundle).find(
          (c) => c.type === "asset" && c.fileName.endsWith(".css")
        );
        const jsChunk = Object.values(bundle).find(
          (c) => c.type === "chunk" && c.fileName.endsWith(".js")
        );
        if (cssChunk && cssChunk.type === "asset" && jsChunk && jsChunk.type === "chunk") {
          const css = String(cssChunk.source).replace(/`/g, "\\`").replace(/\$/g, "\\$");
          jsChunk.code =
            `(function(){var s=document.createElement('style');s.textContent=\`${css}\`;document.head.appendChild(s);}());\n` +
            jsChunk.code;
          delete bundle[cssChunk.fileName];
        }
      },
    },
  ],
  build: {
    outDir: "dist",
    emptyOutDir: false,
    lib: {
      entry: config.entry,
      name: config.name,
      fileName: () => config.fileName,
      formats: ["iife"],
    },
    // Target modern browsers — Elementor itself requires recent Chrome/FF/Safari.
    target: "es2020",
    minify: true,
    sourcemap: false,
  },
});
