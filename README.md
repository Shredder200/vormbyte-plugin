# Vormbyte

**An MCP server for WordPress that lets AI agents understand how a site is built — and edit it the way an experienced WordPress developer would.**

Vormbyte connects AI clients (Claude, Codex, Cursor, Gemini CLI, VS Code/Copilot and other MCP clients) directly to a WordPress site. The agent can run PHP and WP-CLI, inspect the database, read and write files, edit Gutenberg content through the real block editor, and load playbooks (skills) for specific jobs.

> [!WARNING]
> For development and staging sites. With backups. Always.

## What makes it good at editing real sites

Most failed AI edits change a layer that is not the one rendering what the visitor sees. Vormbyte tells the agent, on every connection, how *this* site is built:

- **Site profile** (`includes/site-profile.php`) — environment, homepage setup, block vs classic theme, child/parent theme, which templates and Global Styles are customized in the database, which page builders are active **and how many pages each one owns**, plus SEO, cache, commerce, custom-field, form, multilingual, snippet, backup and security plugins, menus, must-use plugins and constraints such as `DISALLOW_FILE_MODS`. It is part of the agent instructions and returned structured as `site_profile` by `vormbyte/agent-context`.
- **Rules derived from that profile** — e.g. "Elementor owns 14 pages: their content lives in `_elementor_data`, clear Elementor's CSS cache afterwards", "the header is a customized template part in the database", "WooCommerce uses HPOS: use the CRUD API", "a page cache is active: purge before verifying".
- **A working method** — locate the real source → choose the right layer → keep a way back → minimal change → verify as a logged-out visitor → report precisely, plus a clear list of actions that need the user's confirmation.
- **Built-in skills** the agent loads on demand:
  - `wordpress-site-editing` — where every kind of change belongs (content, templates, Customizer, child theme, plugins, menus, widgets, SEO, WooCommerce, forms, translations, redirects) and the classic traps.
  - `page-builder-editing` — storage formats and safe edit/save/cache-clear procedures for Elementor, Divi, Bricks, WPBakery, Beaver Builder, Oxygen, Breakdance, Avada and SiteOrigin.
  - `divi-editing` — Divi 4 shortcodes and Divi 5 blocks, finding the right module, Theme Builder headers/footers, Divi Library and global modules, presets and global colors, dynamic content, Theme Options, new Divi pages and Divi's static CSS.
  - `wordpress-troubleshooting` — critical errors, 500/404/redirect loops, caching, conflicts, email, cron, performance and login problems.
  - plus the existing
- **Page-builder helpers** (`includes/builder-tools.php`) the agent calls through `execute-php`: `vormbyte_builder_of_post()`, `vormbyte_builder_outline()` (element tree with ids/byte offsets, types, labels and text for Divi, Elementor, WPBakery, Avada, Bricks, Beaver Builder, Divi 5 and blocks), `vormbyte_builder_backup()` / `vormbyte_builder_restore()` (builder data is not revisioned by WordPress), and `vormbyte_builder_clear_cache()` (Elementor, Divi, Beaver, Avada, Oxygen CSS plus WP Rocket, LiteSpeed, W3TC, WP Super Cache, SiteGround, Autoptimize, Breeze). `gutenberg-edit-content`, `custom-theme-build`, `vormbyte-design` and `skill-creator`.

## Requirements

- WordPress 6.9 or later, PHP 8.0 or later (with the `sodium` extension)
- An administrator account
- HTTPS for remote connections (local development works without)

## Install

The plugin needs its `vendor/` folder. Use the built ZIP (`vormbyte-<version>.zip`), or build it yourself:

```sh
composer install --no-dev --optimize-autoloader --no-scripts
php bin/scope-mcp-adapter.php vendor --delete-originals
composer dump-autoload --no-dev --optimize
```

Then upload the folder (named `vormbyte`) to `wp-content/plugins/`, activate it, open **Vormbyte → Configuration**, enable AI Abilities and follow the instructions for your AI client.

`bin/scope-mcp-adapter.php` creates a private copy of the WordPress MCP Adapter under the `Vormbyte\Vendor\` namespace with `vormbyte_mcp_adapter_*` hooks, so Vormbyte never collides with the public MCP Adapter plugin.

## Differences from the upstream project

- Renamed everywhere (functions, constants, options, REST/MCP routes, abilities, WP-CLI command `wp vormbyte`, text domain, sandbox folder `wp-content/vormbyte-sandbox/`). Settings from an earlier Novamira install are not carried over.
- No background requests to the upstream project's servers: the update checker is off unless you define `VORMBYTE_UPDATE_INFO_URL`, and the remote "specializations" list is not fetched.
- The Pro upsell (menu entry, notices, cards) and community links are removed. Read Mode and Ghost Mode still need a Pro add-on, which this edition does not include.
- The CLI connection method is hidden: its installer pointed at packages published only under the original name.

## License and credits

Vormbyte is a modified version of Novamira © 2026 Ovation S.r.l., licensed under the GNU Affero General Public License v3.0 or later. Vormbyte is distributed under the same license; see [LICENSE](LICENSE). Original copyright notices are kept in the source files. If you run a modified version for users over a network, the AGPL requires you to offer them the corresponding source.

Bundled third-party components keep their own licenses (see `LICENSES/`).
