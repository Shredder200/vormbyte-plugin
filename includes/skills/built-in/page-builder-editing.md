---
name: page-builder-editing
description: How to read and change content built with page builders without breaking it — Elementor, Divi, Bricks, WPBakery, Beaver Builder, Oxygen, Breakdance, Avada/Fusion, SiteOrigin. Covers where each builder stores page data, how to edit it safely in PHP, theme-builder headers/footers, global widgets/styles, and regenerating CSS caches. Load it whenever a page, header, footer or template is built with a page builder.
---

# Editing page-builder content

Page builders store the real page in their own format, usually in post meta.
`post_content` is often a stale fallback. Always:

1. Detect the builder **per post** (the site may mix builders).
2. Back up the builder data (it is not revisioned by core).
3. Change the smallest possible element, keeping IDs and settings intact.
4. Save through the builder's API when it has one; otherwise write the same
   format back exactly (slashing/serialization matter).
5. Clear the builder's CSS/asset cache and the page cache, then verify on the
   front end.

Vormbyte ships helpers for every step (call them through `vormbyte/execute-php`):
`vormbyte_builder_of_post( $id )`, `vormbyte_builder_outline( $id )` (element
tree with ids/offsets, types, labels and text), `vormbyte_builder_backup( $id )`,
`vormbyte_builder_restore( $id, $key )` and `vormbyte_builder_clear_cache( $id )`.
Start every builder edit with the outline and a backup.

For Divi sites load the dedicated `divi-editing` skill.

Prefer adding or editing elements with the builder's *native* widgets. Do not
drop raw HTML widgets into builder pages unless the user asks — they cannot
edit those visually afterwards.

## Detect the builder of a post

```php
$id = 123;
$builder = match ( true ) {
    get_post_meta( $id, '_elementor_edit_mode', true ) === 'builder' => 'elementor',
    get_post_meta( $id, '_et_pb_use_builder', true ) === 'on'        => 'divi',
    get_post_meta( $id, '_bricks_editor_mode', true ) === 'bricks'   => 'bricks',
    (bool) get_post_meta( $id, '_fl_builder_enabled', true )         => 'beaver',
    get_post_meta( $id, '_wpb_vc_js_status', true ) === 'true'       => 'wpbakery',
    (bool) get_post_meta( $id, '_breakdance_data', true )            => 'breakdance',
    (bool) get_post_meta( $id, '_ct_builder_json', true )
        || (bool) get_post_meta( $id, 'ct_builder_shortcodes', true ) => 'oxygen',
    get_post_meta( $id, 'fusion_builder_status', true ) === 'active' => 'avada',
    (bool) get_post_meta( $id, 'panels_data', true )                 => 'siteorigin',
    has_blocks( get_post_field( 'post_content', $id ) )              => 'blocks',
    default                                                          => 'classic',
};
```

## Elementor

- Data: `_elementor_data` post meta — a JSON array of sections/containers →
  columns → widgets. Each node: `id` (8-char hex), `elType`
  (`section|column|container|widget`), `widgetType`, `settings`, `elements`.
- Also: `_elementor_page_settings`, `_elementor_version`, `_elementor_css`
  (generated), `_elementor_template_type`.
- Read: `$data = json_decode( get_post_meta( $id, '_elementor_data', true ), true );`
- Edit: walk the tree recursively, change `settings` of the target widget only
  (e.g. `heading` → `settings.title`; `text-editor` → `settings.editor`;
  `button` → `settings.text`, `settings.link.url`; `image` →
  `settings.image = ['id'=>…, 'url'=>…]`). New nodes need a unique random
  8-char hex `id`.
- Save (preferred, keeps Elementor's hooks and revisions):
  ```php
  $document = \Elementor\Plugin::$instance->documents->get( $id );
  $document->save( [ 'elements' => $data ] );
  ```
  Fallback: `update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );`
  — the `wp_slash` is required, otherwise escaped quotes in the JSON break.
- Then: `\Elementor\Plugin::$instance->files_manager->clear_cache();`
- Theme Builder (Pro): headers, footers, single/archive templates are
  `elementor_library` posts with `_elementor_template_type`
  (`header`, `footer`, `single-post`, …) and display conditions in
  `_elementor_conditions`. Edit the template, not each page.
- Global widgets/kit: site colors and fonts live in the active Kit
  (`get_option( 'elementor_active_kit' )`), in its `_elementor_page_settings`
  (`system_colors`, `custom_colors`, `system_typography`). Change the kit for
  site-wide styling instead of per-widget overrides.

## Divi

- Divi 4: `post_content` holds shortcodes:
  `[et_pb_section][et_pb_row][et_pb_column type="4_4"][et_pb_text]…[/et_pb_text]…`.
  Edit the specific module's attributes/content; keep `_builder_version` and
  `global_module` attributes. Do not run `wpautop` or the content filters on it.
- Divi 5: block-style markup (`<!-- wp:divi/section … -->`). Keep that format.
- Theme Builder: `et_template` posts linking `et_header_layout`,
  `et_body_layout`, `et_footer_layout`. Global modules are `et_pb_layout`
  posts referenced by `global_module="ID"` — editing the global layout changes
  every page that uses it.
- After edits clear `wp-content/et-cache/` (or `ET_Core_PageResource::remove_static_resources( 'all', 'all' )`).

## Bricks

- Data: `_bricks_page_content_2` (array of flat elements:
  `id`, `name`, `parent`, `children`, `settings`). Header/footer:
  `_bricks_page_header_2` / `_bricks_page_footer_2` on `bricks_template` posts
  (`_bricks_template_type`).
- Bricks hashes data; write back with `update_post_meta()` passing the PHP
  array (WordPress serializes it). Keep parent/children links consistent when
  adding elements; IDs are 6-char lowercase alphanumerics.
- Global classes: `bricks_global_classes` option; theme styles:
  `bricks_theme_styles`. If CSS loading method is "external files",
  regenerate them (Bricks → Settings → Performance) or delete
  `wp-content/uploads/bricks/css`.

## WPBakery

- `post_content` shortcodes: `[vc_row][vc_column][vc_column_text]…`.
- Per-element design options compile into `_wpb_shortcodes_custom_css`
  (`.vc_custom_<timestamp>{…}`); keep `css=".vc_custom_…"` attributes intact.
- Templates: `templatera` posts if Templatera is used.

## Beaver Builder

- `_fl_builder_data` (published) and `_fl_builder_draft` (draft) hold arrays of
  node objects (`node`, `type`, `parent`, `position`, `settings`).
- Use `FLBuilderModel::get_layout_data( 'published', $id )`, change, then
  `FLBuilderModel::update_layout_data( $data, 'published', $id )` and also the
  draft so the editor does not show the old version. Clear with
  `FLBuilderModel::delete_asset_cache_for_all_posts()`.
- Themer layouts: `fl-theme-layout` posts.

## Oxygen

- Oxygen 4+/6: `_ct_builder_json`; Oxygen 3: `ct_builder_shortcodes`.
- Oxygen disables the theme: theme files and Customizer have no effect on the
  front end. Templates are `ct_template` posts.
- After edits regenerate CSS (Oxygen → Settings → CSS Cache → Regenerate) or
  `oxygen_vsb_cache_universal_css()` / `oxygen_vsb_delete_css()` where available.

## Breakdance

- `_breakdance_data` holds JSON (`tree_json_string`) with nodes
  (`id`, `data.type`, `data.properties`, `children`). Decode, edit, re-encode,
  `wp_slash`. Global header/footer: `breakdance_header` / `breakdance_footer`;
  global styles in the `breakdance_global_settings_json_string` option.
- Regenerate cache from Breakdance → Settings → Tools after bulk edits.

## Avada / Fusion Builder

- `post_content` shortcodes (`[fusion_builder_container]…`). Layouts:
  `fusion_tb_layout` / `fusion_tb_section`. Global options: `fusion_options`.
- Reset caches: Avada → Performance → Reset Avada Caches
  (`fusion_reset_all_caches()`).

## SiteOrigin

- `panels_data` meta: `widgets`, `grids`, `grid_cells` arrays. Each widget has
  `panels_info` (`grid`, `cell`, `id`, `class`). Edit the widget fields in place.

## Before you finish

- Open the page in the builder only if a browser is available — otherwise
  verify on the front end that the change shows and that the builder still
  recognizes the page (builder meta untouched, data valid JSON/serialization).
- Tell the user which builder element you changed so they can find it
  visually, and where the backup of the old data is.
