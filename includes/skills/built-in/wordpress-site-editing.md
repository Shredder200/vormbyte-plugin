---
name: wordpress-site-editing
description: The master playbook for changing an existing WordPress site correctly — finding where something really comes from, choosing the right layer (content, builder data, block templates, Customizer, child theme, plugin/mu-plugin, settings), making a reversible minimal change, and verifying it on the live front end. Load it before any edit to someone's site that is more than a one-line content tweak: text, images, menus, header/footer, styling, SEO, WooCommerce products, forms, redirects, URLs, settings, or custom code.
---

# Editing an existing WordPress site

A WordPress site is many layers stacked on top of each other. Most failed edits
change a layer that is not the one rendering the thing the user sees. Work in
this order: **locate → choose layer → back up → change minimally → verify →
report.**

## 1. Locate what the user means

Users describe what they *see* ("the phone number in the header", "the blue
button on the homepage"). Trace it to its source before touching anything.

1. Get the URL. Resolve it to an object:
   `url_to_postid( $url )`, or for archives/terms use `get_queried_object()` in a
   request context, or `wp post list --name=<slug> --post_type=any`.
2. Fetch the rendered HTML (`wp_remote_get( $url )` or `curl`) and search for the
   exact text. The surrounding markup tells you the source:
   - `elementor-element`, `et_pb_`, `vc_row`, `fl-row`, `brxe-`, `ct-section`,
     `bde-`, `fusion-` → a page builder (see the `page-builder-editing` skill).
   - `wp-block-*` classes → block content or a block template/part.
   - `widget`, `widget_*` ids → classic widgets (`sidebars_widgets` option,
     `widget_<type>` options) or block widgets (`widget_block`).
   - `menu-item-<id>` → a nav menu item post (`nav_menu_item`).
   - Text not found in any post → theme template, theme_mod, plugin option,
     shortcode output, translation (`.po/.mo`, Loco, WPML strings), or a
     snippet. Search the database and files:
     `wp db search "exact text" --all-tables` and `grep -rn "exact text"
     wp-content/themes wp-content/plugins wp-content/mu-plugins`.
3. For a post, check which editor owns it:
   - builder meta present (`_elementor_edit_mode = builder`, `_et_pb_use_builder = on`,
     `_fl_builder_enabled`, `_bricks_editor_mode = bricks`, `_breakdance_data`,
     `ct_builder_shortcodes`/`_ct_builder_json`, `_wpb_vc_js_status = true`)
     → edit the builder's data, not `post_content`.
   - `has_blocks( $post->post_content )` → block markup.
   - otherwise → classic HTML (keep it classic).
4. Check the hooks that may rewrite output: `the_content` filters, shortcodes
   (`global $shortcode_tags`), `wp_nav_menu_items`, `gettext` overrides. If a
   value comes out different from what is stored, something filters it.

Never assume from a name or a theme convention. Confirm the source by
changing nothing and reading the data that produces the output.

## 2. Choose the right layer

| What the user wants | Where it belongs |
| --- | --- |
| Text/images on one page | That post, in its existing format (blocks / classic / builder data) |
| Header/footer on a block theme | `wp_template_part` (DB copy if customized, else create the DB copy from the theme file via the Site Editor data) |
| Header/footer on a classic theme | Customizer/theme options first (`get_theme_mods()`, theme options panel), then a child-theme template override |
| Header/footer built with a builder | The builder's theme-builder template (Elementor library, Divi Theme Builder, Bricks template, …) |
| Site colors/fonts/spacing (block theme) | User Global Styles (`wp_global_styles` post for the theme) |
| Small CSS tweak (classic) | Customizer → Additional CSS (`wp_update_custom_css_post()`); read existing CSS first |
| New behaviour, hooks, shortcodes, CPTs | A small dedicated plugin or mu-plugin (or the Vormbyte sandbox) — or the snippet plugin the site already uses |
| Override a theme template | Child theme (create one if missing and the theme is classic); never the parent |
| Menus | `wp_update_nav_menu_item()` for classic menus; `wp_navigation` posts for the Navigation block |
| Widgets | `sidebars_widgets` + `widget_*` options, or `widget_block` for block widgets |
| Site title, tagline, homepage, reading settings | `update_option()` (`blogname`, `blogdescription`, `show_on_front`, `page_on_front`) |
| SEO title/description/robots | The SEO plugin's fields (Yoast `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`; Rank Math `rank_math_title`, `rank_math_description`; AIOSEO via its `aioseo_posts` table / API; SEOPress `_seopress_titles_title`) |
| Redirects | The redirect plugin in use (Redirection, Rank Math, Yoast Premium), else `wp_safe_redirect` in a mu-plugin |
| Products, prices, stock | WooCommerce CRUD: `$p = wc_get_product( $id ); $p->set_regular_price( '19.95' ); $p->save();` |
| Forms | The form plugin's own storage/API (CF7 `wpcf7_contact_form`, Gravity `GFAPI`, WPForms `wpforms` post JSON, Fluent Forms tables) |
| Translations of content | Each language's own post (WPML `icl_object_id` / `wpml_object_id`, Polylang `pll_get_post`) |
| Translations of interface strings | A child-theme/plugin `.mo` file, Loco Translate, WPML String Translation, or a `gettext` filter for a single string |

Never edit WordPress core files, a parent theme, or a third-party plugin's
files. Updates silently erase those changes. If a plugin must behave
differently, use its hooks/filters from a separate plugin.

## 3. Keep a way back

- **Posts:** `wp_update_post()` creates a revision when revisions are enabled.
  Builder data in post meta is *not* revisioned by core — copy the old meta
  value (e.g. into a `_vormbyte_backup_<key>_<time>` meta, or a file in the
  sandbox) before overwriting it.
- **Options/theme_mods:** read and keep the old value before `update_option()`.
- **Files:** copy the file before editing (e.g. `header.php.bak-<date>` outside
  web-executed paths, or keep the old content in your report).
- **Bulk or database-wide:** `wp db export` first, or confirm the user has a
  backup. For URL changes always run `wp search-replace 'old' 'new'
  --all-tables --dry-run` first, then without `--dry-run`. It handles
  serialized data; manual SQL `REPLACE()` corrupts it.

## 4. Change minimally and natively

- Preserve the existing structure: block attributes, builder element IDs,
  CSS classes, anchors, custom CSS, and the editor format. A "rewrite" that
  drops attributes breaks styling the user set by hand.
- Block content: parse with `parse_blocks()`, change the specific block, write
  back with `serialize_blocks()`. For static blocks, the saved HTML must match
  what the block's `save()` produces or the editor shows "This block contains
  unexpected or invalid content" — use the Vormbyte Gutenberg abilities
  (`gutenberg-edit-content` skill), which validate through the real editor.
- Escape and sanitize: `wp_kses_post()` for HTML content, `sanitize_text_field()`
  for plain values, `esc_url_raw()` for URLs. `wp_update_post()` expects slashed
  data — use `wp_slash()` on content containing backslashes or JSON.
- Media: sideload with `media_sideload_image()` / `media_handle_sideload()` so
  attachments, sizes and metadata exist; set alt text in `_wp_attachment_image_alt`.
  Reference attachments by ID where the block/builder does (e.g. `"id":123`).
- New pages: create as draft unless the user asked to publish; give a slug,
  parent, template, and the same editor/builder the rest of the site uses.
- Slugs: changing a published slug breaks links — add a redirect.
- Code: prefix everything, check `function_exists`/`class_exists`, guard with
  `defined( 'ABSPATH' )`, use hooks with explicit priorities, enqueue assets with
  `wp_enqueue_style/script` and versions, never echo raw user input. Test that
  the site still loads after every PHP change.

## 5. Verify like a visitor

1. Purge caches: the page-cache plugin (WP Rocket `rocket_clean_post()`/
   `rocket_clean_domain()`, LiteSpeed `do_action( 'litespeed_purge_all' )`,
   W3TC `w3tc_flush_all()`, WP Super Cache `wp_cache_clear_cache()`, SG
   `sg_cachepress_purge_cache()`), the builder's CSS cache, `wp_cache_flush()`
   for object cache, and remember CDNs (Cloudflare) may need a purge too.
2. Fetch the changed URL(s) without cookies and confirm the new text/markup is
   present and the old is gone.
3. Check for PHP errors: the debug log (`WP_DEBUG_LOG`), the HTTP status, and
   "There has been a critical error" in the HTML.
4. For layout changes, check the page at mobile width too if a browser is
   available, and check one other page that shares the same template.

If the change does not show up, do not repeat the same edit: go back to step 1,
something else renders that output.

## 6. Report

Tell the user, briefly: what changed (post IDs/titles, files, options), how it
was verified, how to undo it (revision, backup meta, copied file), and anything
you noticed but did not change (e.g. "the footer phone number is also hardcoded
in the contact page").

## Ask first

Deleting content, media, users, plugins or themes · changing `siteurl`/`home`,
permalinks, or roles/capabilities · activating, deactivating, or updating
plugins/themes/core · sending email or notifications · touching orders,
payments, subscriptions or customer data · disabling security plugins or
"Discourage search engines" · anything not reversible. Do not ask about
routine, reversible edits the user clearly requested.

## Common traps

- Editing `post_content` of an Elementor/Bricks/Beaver/Breakdance page: nothing
  visible changes.
- Editing a block theme's `parts/header.html` while a customized copy exists
  in the database: nothing visible changes.
- Editing a parent theme: works today, gone after the next theme update.
- Seeing the old version: page cache, builder CSS cache, CDN, or browser cache —
  not a failed edit.
- `update_option( 'siteurl' )` with a typo: locks everyone out. Fix via
  `wp option update` or `WP_HOME`/`WP_SITEURL` in wp-config.php.
- Search-replacing a domain with SQL: corrupts serialized widgets/options.
- Changing a WooCommerce price with `update_post_meta( '_price' )`: lookup
  tables, variations and caches stay stale.
- Writing PHP with a fatal error into an active plugin/theme file: the site goes
  down. Prefer the sandbox for new code; validate with `php -l` first.
