---
name: divi-editing
description: Deep playbook for editing Divi sites (Divi and Extra themes, Divi Builder plugin, Divi 4 shortcodes and Divi 5 blocks) — finding the right module, editing text/buttons/images/links without breaking the layout, Theme Builder headers and footers, Divi Library and global modules, global presets and colors, dynamic content, Theme Options, new Divi pages, and clearing Divi's static CSS. Load it whenever the site uses Divi and the user wants to change a page, header, footer, style, or layout.
---

# Editing a Divi site

Divi keeps almost everything in its own formats. The safe loop is always:
**outline → back up → change one module → save → clear Divi CSS → verify.**

Vormbyte helpers (call them through `vormbyte/execute-php`):

```php
vormbyte_builder_of_post( $id );      // 'divi' (Divi 4 shortcodes) or 'divi5' (block markup)
vormbyte_builder_outline( $id );      // module tree with byte offsets, admin labels, ids, text
$backup = vormbyte_builder_backup( $id );      // keep this key in your report
vormbyte_builder_clear_cache( $id );           // Divi static CSS + page caches
vormbyte_builder_restore( $id, $backup );      // undo if needed
```

## 1. Know which Divi you are on

- Theme: `wp_get_theme()->get_template()` is `Divi` or `Extra`; or the
  `divi-builder` plugin on another theme. Version: `ET_BUILDER_PRODUCT_VERSION`
  / `ET_CORE_VERSION`.
- **Divi 4**: `post_content` is shortcodes
  `[et_pb_section][et_pb_row][et_pb_column type="4_4"][et_pb_text]…`.
- **Divi 5**: `post_content` is block markup `<!-- wp:divi/section {…} -->` with
  JSON attributes (text lives in e.g. `content.innerContent.desktop.value`).
  Never put Divi 4 shortcodes in a Divi 5 page or the other way round.
- A page is a Divi page when `_et_pb_use_builder` is `on`. Pages without it
  render `post_content` as normal WordPress content.

## 2. Find the module the user means

1. Fetch the front-end URL and find the text. The wrapper class tells you the
   module and its index: `et_pb_text_2`, `et_pb_button_0`, `et_pb_image_1`
   (index = order of that module type on the page, starting at 0). Custom
   `module_id`/`module_class` appear as `id`/class too.
2. Run `vormbyte_builder_outline( $id )` and match by text, `admin_label`,
   `module_id`, or the n-th module of that type.
3. If the text is not in the page, it comes from:
   - **Theme Builder** header/footer/body layout (see §4),
   - a **global module** from the Divi Library (`global_module="123"`, see §5),
   - **dynamic content** (`@ET-DC@…@` attribute, see §7),
   - Theme Options / Customizer (logo, footer credits, social icons — §8).

## 3. Edit a Divi 4 module safely

- Change only the target module's attribute or inner content, using the
  `offset` from the outline to locate it; do not regenerate the whole page.
- Keep `_builder_version`, `_module_preset`, `global_colors_info`,
  `global_module`, `saved_tabs`, all responsive variants (`*_tablet`,
  `*_phone`, `*_last_edited`) and hover variants (`*__hover`,
  `*__hover_enabled`). If you change a value that has `_tablet`/`_phone`
  variants, check whether those override it on smaller screens.
- Divi encodes special characters inside attribute values: `"` → `%22`,
  `[` → `%91`, `]` → `%93`, and line breaks in some fields as `%0A`. Write new
  values the same way; never put a raw `"` or `]` inside an attribute.
- Text module (`et_pb_text`) content is HTML between the tags; Divi applies
  `wpautop`-like formatting, so keep paragraphs as `<p>` and do not add blank
  lines inside lists.
- Common fields: heading/blurb `title`; button `button_text`, `button_url`,
  `url_new_window`; image `src`, `alt`, `title_text`, `url`; CTA
  `title`, `button_text`, `button_url` + inner content; fullwidth header
  `title`, `subhead`, `button_one_text`, `button_one_url`; contact form
  `email`, `title`; menu module `menu_id`.
- Save:
  ```php
  wp_update_post( wp_slash( [ 'ID' => $id, 'post_content' => $new_content ] ) );
  vormbyte_builder_clear_cache( $id );
  ```
  (`wp_slash` matters: Divi content often contains backslashes in CSS.)

New modules: copy the structure of an existing sibling module on the same site
(same `_builder_version`, same preset) and change its content, so it matches
the site's styling. New rows/sections: copy an existing one and adjust.

## 4. Theme Builder (headers, footers, templates)

- `et_template` posts define where a layout applies: meta `_et_use_on`
  (e.g. `singular:post_type:page:all`), `_et_exclude_from`, `_et_enabled`,
  `_et_default`, and the layout ids `_et_header_layout_id`,
  `_et_body_layout_id`, `_et_footer_layout_id` (+ `_enabled` flags).
- The layouts themselves are `et_header_layout`, `et_body_layout`,
  `et_footer_layout` posts whose `post_content` is Divi markup — edit them
  exactly like a page (outline, backup, change, clear cache).
- Changing a header/footer layout changes it on every page that template
  applies to — tell the user.

## 5. Divi Library and global items

- Library items are `et_pb_layout` posts; taxonomy `layout_type` (`module`,
  `row`, `section`, `layout`) and `scope` (`global`, `non_global`).
- A module/row/section with `global_module="123"` is global: Divi renders the
  library item 123. Edit `et_pb_layout` 123, not the copy in the page. If the
  page's copy differs, update it too so the Visual Builder shows the same.
- Non-global library items are templates only; editing them does not change
  pages that already used them.

## 6. Global presets and colors

- Presets: `_module_preset="<id>"` on a module; preset definitions live in the
  `et_divi_builder_global_presets_ng` option (per module type). Changing a
  preset restyles every module using it — usually what "make all buttons
  green" means; changing one module's attributes overrides only that module.
- Global colors: `et_global_data` option (`global_colors` => `gcid-…` →
  `color`, `active`). Modules reference them as `gcid-xxxx` and list them in
  `global_colors_info`. To change a brand color site-wide, change the global
  color, not each module.

## 7. Dynamic content

Attributes like `@ET-DC@eyJ…@` are base64 JSON
(`{"dynamic":true,"content":"post_title","settings":{…}}`). The value comes
from the post/ACF field named there — change the source field, or replace the
whole `@ET-DC@…@` token with a literal value only if the user wants static text.

## 8. Theme Options, Customizer, Extra

- `et_divi` option (array): `divi_logo`, `divi_favicon`, `divi_color_palette`,
  `divi_integration_head`/`divi_integration_body` (scripts in head/body),
  `divi_custom_css` (older versions), social links (`divi_facebook_url`, …),
  `divi_show_facebook_icon`, footer credits `custom_footer_credits` and
  `disable_custom_footer_credits`.
- Customizer settings (header style, fonts, colors) also live in `et_divi`.
  Custom CSS on current versions: Customizer → Additional CSS
  (`wp_get_custom_css()`), or Theme Options → Custom CSS.
- Extra theme uses `et_extra` similarly; its category layouts are
  `layout` posts with `_extra_layout_*` meta.

## 9. New Divi pages

```php
$id = wp_insert_post( wp_slash( [
    'post_type'    => 'page',
    'post_status'  => 'draft',
    'post_title'   => 'Nieuwe pagina',
    'post_content' => $divi_markup,          // copied from an existing page's sections
] ) );
update_post_meta( $id, '_et_pb_use_builder', 'on' );
update_post_meta( $id, '_et_pb_page_layout', 'et_no_sidebar' );   // match the site's other pages
update_post_meta( $id, '_et_builder_version', get_post_meta( $existing_id, '_et_builder_version', true ) );
```

Keep it a draft unless the user asked to publish, and give the preview link.

## 10. Before you finish

- Clear caches: `vormbyte_builder_clear_cache( $id )` (Divi static CSS in
  `wp-content/et-cache/<id>/` plus page caches). For Theme Builder or preset
  changes clear everything: `vormbyte_builder_clear_cache()`.
- Fetch the page logged-out; check the change, the layout on mobile width if
  possible, and that no shortcode text (`[et_pb_`) leaks into the page — that
  means broken shortcode syntax: restore the backup and redo the edit.
- If the user has the Visual Builder open on that page, their next save will
  overwrite your change — tell them to reload it first.
- Report the module (type + admin label/position), the backup key, and how to
  undo.
