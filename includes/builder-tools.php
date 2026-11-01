<?php

// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Page-builder helpers an agent can call from `vormbyte/execute-php`.
 *
 * Page builders hide a page's real structure in their own formats (shortcodes, JSON in post meta,
 * serialized arrays). These helpers give one consistent way to answer "what is on this page and
 * where" before editing, to keep a backup of the builder data, and to clear the builder's caches
 * afterwards:
 *
 * - vormbyte_builder_of_post( $post_id )         → 'elementor' | 'divi' | 'divi5' | 'bricks' | …
 * - vormbyte_builder_outline( $post_id )         → compact element tree with ids, types, labels, text
 * - vormbyte_builder_backup( $post_id )          → copies builder data + post_content to a backup meta
 * - vormbyte_builder_restore( $post_id, $key )   → puts a backup back
 * - vormbyte_builder_clear_cache( $post_id )     → clears builder CSS/asset caches and page caches
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Post meta keys that hold each builder's data (what a backup must copy).
 *
 * @return array<string, list<string>>
 */
function vormbyte_builder_data_keys(): array
{
    return [
        'elementor' => ['_elementor_data', '_elementor_page_settings', '_elementor_edit_mode', '_elementor_template_type'],
        'divi' => ['_et_pb_use_builder', '_et_pb_old_content', '_et_pb_page_layout', '_et_builder_version'],
        'divi5' => ['_et_pb_use_builder', '_et_pb_old_content', '_et_pb_page_layout', '_et_builder_version'],
        'bricks' => ['_bricks_page_content_2', '_bricks_page_header_2', '_bricks_page_footer_2', '_bricks_editor_mode'],
        'beaver' => ['_fl_builder_data', '_fl_builder_draft', '_fl_builder_data_settings', '_fl_builder_enabled'],
        'wpbakery' => ['_wpb_vc_js_status', '_wpb_shortcodes_custom_css', '_wpb_post_custom_css'],
        'breakdance' => ['_breakdance_data'],
        'oxygen' => ['_ct_builder_json', 'ct_builder_shortcodes', 'ct_other_template'],
        'avada' => ['fusion_builder_status', '_fusion'],
        'siteorigin' => ['panels_data'],
        'thrive' => ['tve_updated_post', 'tve_custom_css', 'tcb_editor_enabled'],
        'blocks' => [],
        'classic' => [],
    ];
}

/**
 * Which editor owns a post. Checked in the order builders take over the front end.
 */
function vormbyte_builder_of_post(int $post_id): string
{
    $meta = static fn(string $key): string => (string) get_post_meta($post_id, $key, true);
    $content = (string) get_post_field('post_content', $post_id);

    if ($meta('_elementor_edit_mode') === 'builder') {
        return 'elementor';
    }
    if ($meta('_et_pb_use_builder') === 'on') {
        return str_contains($content, '<!-- wp:divi/') ? 'divi5' : 'divi';
    }
    if ($meta('_bricks_editor_mode') === 'bricks') {
        return 'bricks';
    }
    if ($meta('_fl_builder_enabled') !== '' && $meta('_fl_builder_enabled') !== '0') {
        return 'beaver';
    }
    if ($meta('_breakdance_data') !== '') {
        return 'breakdance';
    }
    if ($meta('_ct_builder_json') !== '' || $meta('ct_builder_shortcodes') !== '') {
        return 'oxygen';
    }
    if ($meta('_wpb_vc_js_status') === 'true' || str_contains($content, '[vc_row')) {
        return 'wpbakery';
    }
    if ($meta('fusion_builder_status') === 'active' || str_contains($content, '[fusion_builder_container')) {
        return 'avada';
    }
    if (get_post_meta($post_id, 'panels_data', true)) {
        return 'siteorigin';
    }
    if ($meta('tcb_editor_enabled') !== '' && $meta('tcb_editor_enabled') !== '0') {
        return 'thrive';
    }
    // Divi 4 content can exist without the builder flag (e.g. imported layouts).
    if (str_contains($content, '[et_pb_section')) {
        return 'divi';
    }
    if (function_exists('has_blocks') && has_blocks($content)) {
        return 'blocks';
    }

    return 'classic';
}

/**
 * Short, single-line text preview of builder content.
 */
function vormbyte_builder_snippet(string $text, int $length = 80): string
{
    // Tags become spaces so "<h1>A</h1><p>B</p>" reads "A B", not "AB".
    $text = (string) preg_replace('/<[^>]*>/', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
    $text = trim((string) preg_replace('/\s+/', ' ', $text));
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}

/**
 * Parse shortcode-based builder content (Divi 4, WPBakery, Avada) into a tree, without running
 * any shortcode. Unknown or unbalanced tags are kept as leaves so nothing is silently dropped.
 *
 * Each node: tag, attrs (array), content (inner raw text of leaf nodes), children, offset (byte
 * offset of the opening tag in the source, so an agent can locate and edit it precisely).
 *
 * @return list<array{tag: string, attrs: array<string, string>, content: string, children: list<array<string, mixed>>, offset: int}>
 */
function vormbyte_parse_shortcode_tree(string $content): array
{
    $pattern = '/\[(\/)?([A-Za-z][A-Za-z0-9_\-]*)((?:[^\]"\']|"[^"]*"|\'[^\']*\')*?)(\/)?\]/';
    if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        return [];
    }

    // Tags that are closed somewhere: everything else is self-closing.
    $closed = [];
    foreach ($matches as $m) {
        if ($m[1][0] === '/') {
            $closed[$m[2][0]] = true;
        }
    }

    $root = ['children' => []];
    /** @var list<array<string, mixed>> $stack */
    $stack = [&$root];
    foreach ($matches as $m) {
        $full = $m[0][0];
        $offset = (int) $m[0][1];
        $tag = $m[2][0];
        $is_close = $m[1][0] === '/';
        $self_closing = ($m[4][0] ?? '') === '/' || !isset($closed[$tag]);

        if ($is_close) {
            // Pop to the matching open tag; keep text between open and close for leaves.
            for ($i = count($stack) - 1; $i > 0; $i--) {
                if ($stack[$i]['tag'] === $tag) {
                    $start = $stack[$i]['_inner'];
                    if ($stack[$i]['children'] === []) {
                        $stack[$i]['content'] = substr($content, $start, $offset - $start);
                    }
                    unset($stack[$i]['_inner']);
                    array_splice($stack, $i);
                    break;
                }
            }
            continue;
        }

        $attrs = [];
        if (preg_match_all('/([A-Za-z0-9_\-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $m[3][0], $am, PREG_SET_ORDER)) {
            foreach ($am as $a) {
                $attrs[$a[1]] = $a[2] !== '' ? $a[2] : (($a[3] ?? '') !== '' ? $a[3] : ($a[4] ?? ''));
            }
        }
        $node = [
            'tag' => $tag,
            'attrs' => $attrs,
            'content' => '',
            'children' => [],
            'offset' => $offset,
        ];
        $parent_index = count($stack) - 1;
        $stack[$parent_index]['children'][] = $node;
        if (!$self_closing) {
            $last = count($stack[$parent_index]['children']) - 1;
            $stack[] = &$stack[$parent_index]['children'][$last];
            $stack[count($stack) - 1]['_inner'] = $offset + strlen($full);
        }
    }
    // Remove bookkeeping of tags left open.
    foreach ($stack as $i => &$open) {
        if ($i > 0) {
            unset($open['_inner']);
        }
    }
    unset($open);

    /** @var list<array{tag: string, attrs: array<string, string>, content: string, children: list<array<string, mixed>>, offset: int}> */
    return $root['children'];
}

/**
 * Attributes worth showing in an outline: identifiers, labels and link/image targets.
 *
 * @param array<string, mixed> $attrs
 * @return array<string, string>
 */
function vormbyte_builder_key_attrs(array $attrs): array
{
    $keep = [
        'admin_label', 'module_id', 'module_class', '_module_preset', 'global_module', 'title', 'heading', 'button_text',
        'button_url', 'url', 'link', 'link_option_url', 'src', 'image', 'image_url', 'alt', 'el_id', 'el_class', 'css_id', 'id',
        'text', 'subhead', 'name', 'label',
    ];
    $out = [];
    foreach ($keep as $key) {
        $value = $attrs[$key] ?? null;
        if (is_scalar($value) && (string) $value !== '') {
            $out[$key] = vormbyte_builder_snippet((string) $value, 60);
        } elseif (is_array($value) && isset($value['url']) && is_string($value['url'])) {
            $out[$key] = $value['url'];
        }
    }
    return $out;
}

/**
 * A compact outline of a post's builder structure.
 *
 * @return array{post_id: int, title: string, builder: string, nodes: list<array<string, mixed>>, truncated: bool, notes: list<string>}
 */
function vormbyte_builder_outline(int $post_id, int $max_nodes = 400): array
{
    $builder = vormbyte_builder_of_post($post_id);
    $content = (string) get_post_field('post_content', $post_id);
    $nodes = [];
    $notes = [];
    $count = 0;

    $add = static function (array $node) use (&$nodes, &$count, $max_nodes): bool {
        if ($count >= $max_nodes) {
            return false;
        }
        $nodes[] = $node;
        $count++;
        return true;
    };

    switch ($builder) {
        case 'elementor':
            $data = json_decode((string) get_post_meta($post_id, '_elementor_data', true), associative: true);
            $walk = static function (array $elements, int $depth) use (&$walk, $add): void {
                foreach ($elements as $el) {
                    if (!is_array($el)) {
                        continue;
                    }
                    /** @var array<string, mixed> $settings */
                    $settings = is_array($el['settings'] ?? null) ? $el['settings'] : [];
                    $text = '';
                    foreach (['title', 'editor', 'text', 'description_text', 'title_text', 'html', 'testimonial_content'] as $k) {
                        if (is_string($settings[$k] ?? null) && $settings[$k] !== '') {
                            $text = vormbyte_builder_snippet($settings[$k]);
                            break;
                        }
                    }
                    if (!$add([
                        'depth' => $depth,
                        'id' => (string) ($el['id'] ?? ''),
                        'type' => (string) ($el['widgetType'] ?? $el['elType'] ?? ''),
                        'text' => $text,
                        'attrs' => vormbyte_builder_key_attrs($settings + ['css_id' => $settings['_element_id'] ?? '']),
                    ])) {
                        return;
                    }
                    if (is_array($el['elements'] ?? null)) {
                        $walk($el['elements'], $depth + 1);
                    }
                }
            };
            $walk(is_array($data) ? $data : [], 0);
            $notes[] = 'Edit `_elementor_data` (JSON) by element id; save with \\Elementor\\Plugin::$instance->documents->get( $id )->save( [ "elements" => $data ] ) or update_post_meta with wp_slash( wp_json_encode() ). Then vormbyte_builder_clear_cache( $id ).';
            break;

        case 'divi':
        case 'wpbakery':
        case 'avada':
            $walk = static function (array $tree, int $depth) use (&$walk, $add): void {
                foreach ($tree as $n) {
                    if (!$add([
                        'depth' => $depth,
                        'offset' => $n['offset'],
                        'type' => $n['tag'],
                        'text' => vormbyte_builder_snippet((string) $n['content']),
                        'attrs' => vormbyte_builder_key_attrs($n['attrs']),
                    ])) {
                        return;
                    }
                    $walk($n['children'], $depth + 1);
                }
            };
            $walk(vormbyte_parse_shortcode_tree($content), 0);
            $notes[] = 'Content is shortcodes in post_content; `offset` is the byte position of each opening tag. Change only the target module\'s attributes or inner text, keep _builder_version/global_module/css attributes, save with wp_update_post( wp_slash( [ "ID" => $id, "post_content" => $new ] ) ). Then vormbyte_builder_clear_cache( $id ).';
            break;

        case 'divi5':
        case 'blocks':
            $walk = static function (array $blocks, int $depth) use (&$walk, $add): void {
                foreach ($blocks as $b) {
                    if (($b['blockName'] ?? null) === null && trim((string) ($b['innerHTML'] ?? '')) === '') {
                        continue;
                    }
                    /** @var array<string, mixed> $attrs */
                    $attrs = is_array($b['attrs'] ?? null) ? $b['attrs'] : [];
                    if (!$add([
                        'depth' => $depth,
                        'type' => (string) ($b['blockName'] ?? 'html'),
                        'text' => vormbyte_builder_snippet((string) ($b['innerHTML'] ?? '')),
                        'attrs' => vormbyte_builder_key_attrs($attrs + ['id' => $attrs['anchor'] ?? '']),
                    ])) {
                        return;
                    }
                    $walk(is_array($b['innerBlocks'] ?? null) ? $b['innerBlocks'] : [], $depth + 1);
                }
            };
            $walk(function_exists('parse_blocks') ? parse_blocks($content) : [], 0);
            $notes[] = $builder === 'divi5'
                ? 'Divi 5 stores modules as block markup (`divi/*`) in post_content. Edit with parse_blocks()/serialize_blocks(), keep every attribute, then vormbyte_builder_clear_cache( $id ).'
                : 'Native blocks: use the Vormbyte Gutenberg abilities (gutenberg-edit-content skill) for static blocks so the editor validates the markup.';
            break;

        case 'bricks':
            $elements = get_post_meta($post_id, '_bricks_page_content_2', true);
            foreach (is_array($elements) ? $elements : [] as $el) {
                if (!is_array($el)) {
                    continue;
                }
                /** @var array<string, mixed> $settings */
                $settings = is_array($el['settings'] ?? null) ? $el['settings'] : [];
                if (!$add([
                    'id' => (string) ($el['id'] ?? ''),
                    'parent' => (string) ($el['parent'] ?? ''),
                    'type' => (string) ($el['name'] ?? ''),
                    'text' => vormbyte_builder_snippet((string) ($settings['text'] ?? $settings['content'] ?? '')),
                    'attrs' => vormbyte_builder_key_attrs($settings + ['label' => $el['label'] ?? '']),
                ])) {
                    break;
                }
            }
            $notes[] = 'Bricks elements are a flat list linked by parent/children ids in `_bricks_page_content_2`. Update the PHP array with update_post_meta(), keep ids consistent, then vormbyte_builder_clear_cache( $id ).';
            break;

        case 'beaver':
            $data = get_post_meta($post_id, '_fl_builder_data', true);
            foreach (is_array($data) ? $data : [] as $node_id => $node) {
                $node = (array) $node;
                $settings = (array) ($node['settings'] ?? []);
                if (!$add([
                    'id' => (string) $node_id,
                    'parent' => (string) ($node['parent'] ?? ''),
                    'type' => (string) ($node['type'] ?? '') . (isset($settings['type']) && is_string($settings['type']) ? ':' . $settings['type'] : ''),
                    'text' => vormbyte_builder_snippet((string) (is_string($settings['text'] ?? null) ? $settings['text'] : (is_string($settings['heading'] ?? null) ? $settings['heading'] : ''))),
                    'attrs' => vormbyte_builder_key_attrs($settings),
                ])) {
                    break;
                }
            }
            $notes[] = 'Edit with FLBuilderModel::get_layout_data() / update_layout_data() for both "published" and "draft", then vormbyte_builder_clear_cache( $id ).';
            break;

        default:
            $notes[] = $builder === 'classic'
                ? 'Classic HTML content in post_content. Keep it classic (do not convert to blocks unasked).'
                : 'No outline parser for ' . $builder . '; inspect its meta keys: ' . implode(', ', vormbyte_builder_data_keys()[$builder] ?? []);
    }

    return [
        'post_id' => $post_id,
        'title' => get_the_title($post_id),
        'builder' => $builder,
        'nodes' => $nodes,
        'truncated' => $count >= $max_nodes,
        'notes' => $notes,
    ];
}

/**
 * Copy a post's builder data and content into one backup meta entry before editing.
 * Builder meta is not revisioned by WordPress, so this is the way back.
 *
 * @return string The backup meta key, for vormbyte_builder_restore().
 */
function vormbyte_builder_backup(int $post_id): string
{
    $builder = vormbyte_builder_of_post($post_id);
    $backup = ['builder' => $builder, 'time' => time(), 'post_content' => (string) get_post_field('post_content', $post_id), 'meta' => []];
    foreach (vormbyte_builder_data_keys()[$builder] ?? [] as $key) {
        if (metadata_exists('post', $post_id, $key)) {
            $backup['meta'][$key] = get_post_meta($post_id, $key, true);
        }
    }
    $key = '_vormbyte_backup_' . gmdate('Ymd_His');
    // Stored slashed-safe: add_post_meta unslashes, so slash the serialized payload.
    add_post_meta($post_id, $key, wp_slash($backup));

    return $key;
}

/**
 * Restore a backup made by vormbyte_builder_backup().
 */
function vormbyte_builder_restore(int $post_id, string $backup_key): bool
{
    $backup = get_post_meta($post_id, $backup_key, true);
    if (!is_array($backup) || !isset($backup['post_content'], $backup['meta']) || !is_array($backup['meta'])) {
        return false;
    }
    wp_update_post(wp_slash(['ID' => $post_id, 'post_content' => (string) $backup['post_content']]));
    foreach ($backup['meta'] as $key => $value) {
        update_post_meta($post_id, (string) $key, wp_slash($value));
    }
    vormbyte_builder_clear_cache($post_id);

    return true;
}

/**
 * Clear builder-generated CSS/assets and page caches, for one post or the whole site.
 *
 * @return list<string> What was cleared, for the agent's report.
 */
function vormbyte_builder_clear_cache(?int $post_id = null): array
{
    $done = [];
    $try = static function (string $label, callable $callback) use (&$done): void {
        try {
            $callback();
            $done[] = $label;
        } catch (\Throwable) {
            // A cache that cannot be cleared must not break the edit that triggered it.
        }
    };

    if (class_exists('\Elementor\Plugin')) {
        $try('Elementor CSS', static function () use ($post_id): void {
            $plugin = \Elementor\Plugin::$instance;
            if ($post_id !== null && class_exists('\Elementor\Core\Files\CSS\Post')) {
                \Elementor\Core\Files\CSS\Post::create($post_id)->delete();
            }
            $plugin->files_manager->clear_cache();
        });
    }
    if (class_exists('ET_Core_PageResource')) {
        $try('Divi static CSS', static function () use ($post_id): void {
            \ET_Core_PageResource::remove_static_resources($post_id !== null ? (string) $post_id : 'all', 'all');
        });
    } elseif (defined('ET_CORE_VERSION') && is_dir(WP_CONTENT_DIR . '/et-cache')) {
        $done[] = 'Divi: delete wp-content/et-cache manually if styles look stale';
    }
    if (class_exists('FLBuilderModel')) {
        $try('Beaver Builder assets', static function () use ($post_id): void {
            $post_id !== null
                ? \FLBuilderModel::delete_all_asset_cache($post_id)
                : \FLBuilderModel::delete_asset_cache_for_all_posts();
        });
    }
    if (function_exists('fusion_reset_all_caches')) {
        $try('Avada caches', static fn() => fusion_reset_all_caches());
    }
    if (function_exists('oxygen_vsb_delete_css') && $post_id !== null) {
        $try('Oxygen CSS', static fn() => oxygen_vsb_delete_css($post_id));
    }
    if ($post_id !== null) {
        $try('WordPress post cache', static fn() => clean_post_cache($post_id));
    }

    // Page caches.
    if (function_exists('rocket_clean_post') && $post_id !== null) {
        $try('WP Rocket (post)', static fn() => rocket_clean_post($post_id));
    } elseif (function_exists('rocket_clean_domain')) {
        $try('WP Rocket', static fn() => rocket_clean_domain());
    }
    if (has_action('litespeed_purge_all')) {
        $try('LiteSpeed', static fn() => $post_id !== null ? do_action('litespeed_purge_post', $post_id) : do_action('litespeed_purge_all'));
    }
    if (function_exists('w3tc_flush_post') && $post_id !== null) {
        $try('W3 Total Cache (post)', static fn() => w3tc_flush_post($post_id));
    } elseif (function_exists('w3tc_flush_all')) {
        $try('W3 Total Cache', static fn() => w3tc_flush_all());
    }
    if (function_exists('wp_cache_post_change') && $post_id !== null) {
        $try('WP Super Cache (post)', static fn() => wp_cache_post_change($post_id));
    }
    if (function_exists('sg_cachepress_purge_cache')) {
        $try('SiteGround Optimizer', static fn() => sg_cachepress_purge_cache());
    }
    if (class_exists('autoptimizeCache')) {
        $try('Autoptimize', static fn() => \autoptimizeCache::clearall());
    }
    if (has_action('breeze_clear_all_cache')) {
        $try('Breeze', static fn() => do_action('breeze_clear_all_cache'));
    }

    return $done;
}
