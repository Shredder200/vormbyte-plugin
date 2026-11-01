<?php

// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Site profile: a structured picture of how this particular WordPress site is built, so an agent
 * knows *where* a change belongs before it makes one.
 *
 * The inventory of installed plugins alone does not tell an agent that the homepage is an Elementor
 * document whose content lives in post meta, that the header is a block template part customized in
 * the database, or that orders are stored in HPOS tables. Editing the wrong layer is the most common
 * way an agent "succeeds" without changing anything visible, or breaks a site on the next update.
 * This file detects those facts and turns them into concrete editing rules.
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Page builders: how to detect them and where they keep a page's content.
 *
 * `meta` is the post meta that marks a post as built with the builder (with an optional required
 * value); `storage` tells the agent where the visible content actually lives.
 *
 * @return array<string, array{label: string, plugins: list<string>, themes: list<string>, constants: list<string>, meta: string, meta_value: string, storage: string}>
 */
function vormbyte_site_profile_builder_catalog(): array
{
    return [
        'elementor' => [
            'label' => 'Elementor',
            'plugins' => ['elementor'],
            'themes' => [],
            'constants' => ['ELEMENTOR_VERSION'],
            'meta' => '_elementor_edit_mode',
            'meta_value' => 'builder',
            'storage' => 'post meta `_elementor_data` (JSON, slashed); post_content is only a fallback copy. After changing it, clear Elementor\'s generated CSS: `\\Elementor\\Plugin::$instance->files_manager->clear_cache()`. Theme Builder headers/footers/templates are `elementor_library` posts with conditions in `_elementor_conditions`.',
        ],
        'divi' => [
            'label' => 'Divi Builder',
            'plugins' => ['divi-builder'],
            'themes' => ['Divi', 'Extra'],
            'constants' => ['ET_BUILDER_VERSION'],
            'meta' => '_et_pb_use_builder',
            'meta_value' => 'on',
            'storage' => 'post_content as `[et_pb_section]…` shortcodes (Divi 4) or `<!-- wp:divi/… -->` block markup (Divi 5). Theme Builder layouts are `et_template`/`et_header_layout`/`et_body_layout`/`et_footer_layout` posts; global modules are `et_pb_layout` library posts; presets and global colors are options. Load the `divi-editing` skill, and clear Divi\'s static CSS with `vormbyte_builder_clear_cache( $id )` after edits.',
        ],
        'wpbakery' => [
            'label' => 'WPBakery Page Builder',
            'plugins' => ['js_composer'],
            'themes' => [],
            'constants' => ['WPB_VC_VERSION'],
            'meta' => '_wpb_vc_js_status',
            'meta_value' => 'true',
            'storage' => 'post_content as `[vc_row][vc_column]…` shortcodes; custom CSS in `_wpb_shortcodes_custom_css` post meta.',
        ],
        'beaver' => [
            'label' => 'Beaver Builder',
            'plugins' => ['beaver-builder-lite-version', 'bb-plugin'],
            'themes' => [],
            'constants' => ['FL_BUILDER_VERSION'],
            'meta' => '_fl_builder_enabled',
            'meta_value' => '1',
            'storage' => 'post meta `_fl_builder_data` (published) and `_fl_builder_draft` (serialized PHP objects). Edit through `FLBuilderModel`, then `FLBuilderModel::delete_asset_cache_for_all_posts()`.',
        ],
        'bricks' => [
            'label' => 'Bricks',
            'plugins' => [],
            'themes' => ['bricks'],
            'constants' => ['BRICKS_VERSION'],
            'meta' => '_bricks_editor_mode',
            'meta_value' => 'bricks',
            'storage' => 'post meta `_bricks_page_content_2` (array of elements with id/parent/children/settings); header/footer in `_bricks_page_header_2`/`_bricks_page_footer_2` of `bricks_template` posts. Regenerate CSS files if external CSS loading is enabled.',
        ],
        'oxygen' => [
            'label' => 'Oxygen',
            'plugins' => ['oxygen'],
            'themes' => [],
            'constants' => ['CT_VERSION'],
            'meta' => 'ct_builder_shortcodes',
            'meta_value' => '',
            'storage' => 'post meta `ct_builder_shortcodes` (Oxygen 3) or `_ct_builder_json` (Oxygen 4+); templates are `ct_template` posts. Oxygen disables the theme entirely, so theme files have no effect.',
        ],
        'breakdance' => [
            'label' => 'Breakdance',
            'plugins' => ['breakdance'],
            'themes' => [],
            'constants' => ['__BREAKDANCE_VERSION'],
            'meta' => '_breakdance_data',
            'meta_value' => '',
            'storage' => 'post meta `_breakdance_data` (JSON tree); global header/footer are `breakdance_header`/`breakdance_footer` posts. Regenerate cache from Breakdance → Settings → Tools after bulk edits.',
        ],
        'avada' => [
            'label' => 'Avada / Fusion Builder',
            'plugins' => ['fusion-builder'],
            'themes' => ['Avada'],
            'constants' => ['FUSION_BUILDER_VERSION'],
            'meta' => 'fusion_builder_status',
            'meta_value' => 'active',
            'storage' => 'post_content as `[fusion_builder_container]…` shortcodes; Avada Layouts are `fusion_tb_layout`/`fusion_tb_section` posts. Reset Fusion caches (Avada → Performance) after edits.',
        ],
        'siteorigin' => [
            'label' => 'SiteOrigin Page Builder',
            'plugins' => ['siteorigin-panels'],
            'themes' => [],
            'constants' => ['SITEORIGIN_PANELS_VERSION'],
            'meta' => 'panels_data',
            'meta_value' => '',
            'storage' => 'post meta `panels_data` (serialized array of widgets/grids/cells).',
        ],
        'brizy' => [
            'label' => 'Brizy',
            'plugins' => ['brizy', 'brizy-pro'],
            'themes' => [],
            'constants' => ['BRIZY_VERSION'],
            'meta' => 'brizy_post_uid',
            'meta_value' => '',
            'storage' => 'post meta `brizy` (base64 JSON, compiled HTML separately). Prefer editing in the Brizy editor; direct edits must recompile.',
        ],
        'thrive' => [
            'label' => 'Thrive Architect',
            'plugins' => ['thrive-visual-editor'],
            'themes' => [],
            'constants' => ['TVE_VERSION'],
            'meta' => 'tcb_editor_enabled',
            'meta_value' => '1',
            'storage' => 'post meta `tve_updated_post` (HTML) plus `tve_custom_css`.',
        ],
    ];
}

/**
 * Plugins that change how a task must be done, grouped by concern. Detected by active plugin
 * folder, so the list stays cheap to evaluate.
 *
 * @return array<string, array<string, string>> concern => [plugin folder => label]
 */
function vormbyte_site_profile_plugin_catalog(): array
{
    return [
        'seo' => [
            'wordpress-seo' => 'Yoast SEO',
            'wordpress-seo-premium' => 'Yoast SEO Premium',
            'seo-by-rank-math' => 'Rank Math',
            'all-in-one-seo-pack' => 'All in One SEO',
            'all-in-one-seo-pack-pro' => 'All in One SEO Pro',
            'wp-seopress' => 'SEOPress',
            'autodescription' => 'The SEO Framework',
            'slim-seo' => 'Slim SEO',
        ],
        'cache' => [
            'wp-rocket' => 'WP Rocket',
            'litespeed-cache' => 'LiteSpeed Cache',
            'w3-total-cache' => 'W3 Total Cache',
            'wp-super-cache' => 'WP Super Cache',
            'wp-fastest-cache' => 'WP Fastest Cache',
            'sg-cachepress' => 'SiteGround Optimizer',
            'autoptimize' => 'Autoptimize',
            'breeze' => 'Breeze',
            'cache-enabler' => 'Cache Enabler',
            'nginx-helper' => 'Nginx Helper',
            'cloudflare' => 'Cloudflare',
            'perfmatters' => 'Perfmatters',
            'flying-press' => 'FlyingPress',
            'swift-performance-lite' => 'Swift Performance',
            'hummingbird-performance' => 'Hummingbird',
        ],
        'commerce' => [
            'woocommerce' => 'WooCommerce',
            'easy-digital-downloads' => 'Easy Digital Downloads',
            'surecart' => 'SureCart',
            'memberpress' => 'MemberPress',
            'paid-memberships-pro' => 'Paid Memberships Pro',
        ],
        'fields' => [
            'advanced-custom-fields' => 'ACF',
            'advanced-custom-fields-pro' => 'ACF Pro',
            'secure-custom-fields' => 'Secure Custom Fields',
            'meta-box' => 'Meta Box',
            'pods' => 'Pods',
            'jet-engine' => 'JetEngine',
            'custom-post-type-ui' => 'Custom Post Type UI',
            'acpt' => 'ACPT',
            'types' => 'Toolset Types',
        ],
        'forms' => [
            'contact-form-7' => 'Contact Form 7',
            'gravityforms' => 'Gravity Forms',
            'wpforms-lite' => 'WPForms Lite',
            'wpforms' => 'WPForms',
            'fluentform' => 'Fluent Forms',
            'ninja-forms' => 'Ninja Forms',
            'formidable' => 'Formidable Forms',
            'forminator' => 'Forminator',
        ],
        'blocks' => [
            'kadence-blocks' => 'Kadence Blocks',
            'ultimate-addons-for-gutenberg' => 'Spectra',
            'generateblocks' => 'GenerateBlocks',
            'stackable-ultimate-gutenberg-blocks' => 'Stackable',
            'greenshift-animation-and-page-builder-blocks' => 'Greenshift',
            'otter-blocks' => 'Otter Blocks',
            'essential-blocks' => 'Essential Blocks',
            'gutenberg' => 'Gutenberg (plugin)',
        ],
        'builder_addons' => [
            'elementor-pro' => 'Elementor Pro',
            'essential-addons-for-elementor-lite' => 'Essential Addons for Elementor',
            'jet-elements' => 'JetElements',
            'premium-addons-for-elementor' => 'Premium Addons for Elementor',
            'ultimate-elementor' => 'Ultimate Addons for Elementor',
        ],
        'security' => [
            'wordfence' => 'Wordfence',
            'better-wp-security' => 'Solid Security',
            'sucuri-scanner' => 'Sucuri',
            'all-in-one-wp-security-and-firewall' => 'All-In-One Security',
            'wps-hide-login' => 'WPS Hide Login',
        ],
        'backup' => [
            'updraftplus' => 'UpdraftPlus',
            'backwpup' => 'BackWPup',
            'duplicator' => 'Duplicator',
            'duplicator-pro' => 'Duplicator Pro',
            'all-in-one-wp-migration' => 'All-in-One WP Migration',
            'blogvault-real-time-backup' => 'BlogVault',
            'jetpack' => 'Jetpack (may include VaultPress Backup)',
            'wpvivid-backuprestore' => 'WPvivid',
        ],
        'editor' => [
            'classic-editor' => 'Classic Editor',
            'disable-gutenberg' => 'Disable Gutenberg',
            'classic-widgets' => 'Classic Widgets',
        ],
        'snippets' => [
            'code-snippets' => 'Code Snippets',
            'insert-headers-and-footers' => 'WPCode',
            'wpcode-premium' => 'WPCode Pro',
            'fluent-snippets' => 'FluentSnippets',
        ],
        'redirects' => [
            'redirection' => 'Redirection',
            'safe-redirect-manager' => 'Safe Redirect Manager',
            '301-redirects' => '301 Redirects',
        ],
        'smtp' => [
            'wp-mail-smtp' => 'WP Mail SMTP',
            'fluent-smtp' => 'FluentSMTP',
            'post-smtp' => 'Post SMTP',
        ],
    ];
}

/**
 * Detect which page builders are active and how many published or draft posts use each.
 *
 * @param array<string, true> $folders active plugin folders
 * @return list<array{key: string, label: string, posts: int, storage: string}>
 */
function vormbyte_site_profile_detect_builders(array $folders, WP_Theme $theme): array
{
    $theme_slugs = [$theme->get_stylesheet(), $theme->get_template()];
    $active = [];
    foreach (vormbyte_site_profile_builder_catalog() as $key => $builder) {
        $is_active = false;
        foreach ($builder['plugins'] as $plugin) {
            $is_active = $is_active || isset($folders[$plugin]);
        }
        foreach ($builder['themes'] as $theme_slug) {
            $is_active = $is_active || in_array($theme_slug, $theme_slugs, strict: true);
        }
        foreach ($builder['constants'] as $constant) {
            $is_active = $is_active || defined($constant);
        }
        if ($is_active) {
            $active[$key] = $builder;
        }
    }

    if ($active === []) {
        return [];
    }

    // @mago-expect lint:no-global
    global $wpdb;
    $conditions = [];
    foreach ($active as $builder) {
        $conditions[] = $builder['meta_value'] === ''
            ? $wpdb->prepare('(pm.meta_key = %s)', $builder['meta'])
            : $wpdb->prepare('(pm.meta_key = %s AND pm.meta_value = %s)', $builder['meta'], $builder['meta_value']);
    }

    // One grouped query for every builder: cheap even on large sites thanks to the meta_key index.
    /** @var list<object{meta_key: string, posts: string}>|null $rows */
    $rows = $wpdb->get_results(
        "SELECT pm.meta_key, COUNT(DISTINCT pm.post_id) AS posts FROM {$wpdb->postmeta} pm "
        . "INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id "
        . "WHERE p.post_status IN ('publish','draft','pending','private','future') "
        // Builder templates and kits are not pages: counting them would report a builder as
        // "owning" content on a site where no page uses it yet.
        . "AND p.post_type NOT IN ('revision','nav_menu_item','elementor_library','et_pb_layout','bricks_template',"
        . "'fl-builder-template','ct_template','breakdance_header','breakdance_footer','fusion_tb_layout','fusion_tb_section') AND ("
        . implode(' OR ', $conditions)
        . ') GROUP BY pm.meta_key',
    );
    $counts = [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        $counts[(string) $row->meta_key] = (int) $row->posts;
    }

    $result = [];
    foreach ($active as $key => $builder) {
        $result[] = [
            'key' => $key,
            'label' => $builder['label'],
            'posts' => $counts[$builder['meta']] ?? 0,
            'storage' => $builder['storage'],
        ];
    }
    return $result;
}

/**
 * Count the block-theme templates and template parts that were customized in the database. A
 * customized copy overrides the theme's file, so editing the file alone changes nothing.
 *
 * @return array{templates: list<string>, parts: list<string>, global_styles: bool}
 */
function vormbyte_site_profile_block_customizations(WP_Theme $theme): array
{
    $result = ['templates' => [], 'parts' => [], 'global_styles' => false];
    foreach (['wp_template' => 'templates', 'wp_template_part' => 'parts'] as $post_type => $key) {
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => 50,
            'no_found_rows' => true,
            'tax_query' => [[
                'taxonomy' => 'wp_theme',
                'field' => 'name',
                'terms' => [$theme->get_stylesheet()],
            ]],
        ]);
        foreach ($posts as $post) {
            $result[$key][] = $post->post_name;
        }
    }

    $styles = get_posts([
        'post_type' => 'wp_global_styles',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'no_found_rows' => true,
        'tax_query' => [[
            'taxonomy' => 'wp_theme',
            'field' => 'name',
            'terms' => [$theme->get_stylesheet()],
        ]],
    ]);
    if ($styles !== []) {
        $content = json_decode((string) $styles[0]->post_content, associative: true);
        // An untouched record holds only the version and the theme-json marker.
        $result['global_styles'] =
            is_array($content) && array_diff(array_keys($content), ['version', 'isGlobalStylesUserThemeJSON']) !== [];
    }

    return $result;
}

/**
 * The structured site profile. Memoized per request because the instructions, agent-context and
 * Chat can all ask for it while handling one request.
 *
 * @return array<string, mixed>
 */
function vormbyte_site_profile(): array
{
    /** @var array<string, mixed>|null $cached */
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    if (!function_exists('get_mu_plugins') && is_file(ABSPATH . 'wp-admin/includes/plugin.php')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $folders = function_exists('vormbyte_active_folders') ? vormbyte_active_folders() : [];
    $theme = wp_get_theme();
    $parent = $theme->parent();
    $is_block_theme = function_exists('wp_is_block_theme') && wp_is_block_theme();

    $show_on_front = (string) get_option('show_on_front', default_value: 'posts');
    $front_id = (int) get_option('page_on_front', default_value: 0);
    $posts_id = (int) get_option('page_for_posts', default_value: 0);

    $plugins = [];
    foreach (vormbyte_site_profile_plugin_catalog() as $concern => $catalog) {
        foreach ($catalog as $folder => $label) {
            if (isset($folders[$folder])) {
                $plugins[$concern][] = $label;
            }
        }
    }

    $content = [];
    foreach (get_post_types(['public' => true], output: 'objects') as $post_type) {
        if ($post_type->name === 'attachment') {
            continue;
        }
        $counts = wp_count_posts($post_type->name);
        $published = (int) ($counts->publish ?? 0);
        $drafts = (int) ($counts->draft ?? 0);
        if ($published === 0 && $drafts === 0) {
            continue;
        }
        $content[$post_type->name] = ['label' => (string) $post_type->label, 'published' => $published, 'drafts' => $drafts];
    }

    $mu_plugins = [];
    if (function_exists('get_mu_plugins') && defined('WPMU_PLUGIN_DIR') && is_dir(WPMU_PLUGIN_DIR)) {
        foreach (array_keys(get_mu_plugins()) as $file) {
            $mu_plugins[] = $file;
        }
    }

    $woocommerce = null;
    if (isset($folders['woocommerce']) || class_exists('WooCommerce')) {
        $hpos = class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        $checkout_id = (int) get_option('woocommerce_checkout_page_id', default_value: 0);
        $woocommerce = [
            'hpos' => $hpos,
            'shop_page' => (int) get_option('woocommerce_shop_page_id', default_value: 0),
            'currency' => (string) get_option('woocommerce_currency', default_value: ''),
            'block_cart_checkout' => $checkout_id > 0 && has_block('woocommerce/checkout', $checkout_id),
        ];
    }

    $menus = [];
    foreach (wp_get_nav_menus() as $menu) {
        $menus[] = $menu->name;
    }
    $locations = get_nav_menu_locations();
    $registered_locations = get_registered_nav_menus();

    $profile = [
        'site' => [
            'name' => get_bloginfo('name'),
            'home_url' => home_url('/'),
            'site_url' => site_url('/'),
            'environment' => wp_get_environment_type(),
            'multisite' => is_multisite(),
            'https' => str_starts_with(home_url(), 'https://'),
            'permalinks' => (string) get_option('permalink_structure', default_value: ''),
            'timezone' => wp_timezone_string(),
            'search_engines_discouraged' => (string) get_option('blog_public', default_value: '1') === '0',
        ],
        'front' => [
            'shows' => $show_on_front,
            'front_page' => $show_on_front === 'page' && $front_id > 0 ? ['id' => $front_id, 'title' => get_the_title($front_id)] : null,
            'posts_page' => $posts_id > 0 ? ['id' => $posts_id, 'title' => get_the_title($posts_id)] : null,
        ],
        'theme' => [
            'name' => (string) $theme->get('Name'),
            'version' => (string) $theme->get('Version'),
            'stylesheet' => $theme->get_stylesheet(),
            'template' => $theme->get_template(),
            'is_child' => $parent instanceof WP_Theme,
            'parent' => $parent instanceof WP_Theme ? (string) $parent->get('Name') : null,
            'block_theme' => $is_block_theme,
            'has_theme_json' => wp_theme_has_theme_json(),
            'directory' => $theme->get_stylesheet_directory(),
            'writable' => wp_is_writable($theme->get_stylesheet_directory()),
            'customizer_css' => trim(wp_get_custom_css()) !== '',
        ],
        'block_customizations' => $is_block_theme ? vormbyte_site_profile_block_customizations($theme) : null,
        'builders' => vormbyte_site_profile_detect_builders($folders, $theme),
        'plugins' => $plugins,
        'woocommerce' => $woocommerce,
        'menus' => [
            'menus' => $menus,
            'locations' => $registered_locations,
            'assigned' => array_filter($locations),
        ],
        'content' => $content,
        'mu_plugins' => $mu_plugins,
        'constraints' => [
            'disallow_file_mods' => defined('DISALLOW_FILE_MODS') && constant('DISALLOW_FILE_MODS'),
            'disallow_file_edit' => defined('DISALLOW_FILE_EDIT') && constant('DISALLOW_FILE_EDIT'),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log' => defined('WP_DEBUG_LOG') ? (is_string(WP_DEBUG_LOG) ? WP_DEBUG_LOG : (bool) WP_DEBUG_LOG) : false,
            'page_cache_dropin' => defined('WP_CACHE') && WP_CACHE && file_exists(WP_CONTENT_DIR . '/advanced-cache.php'),
            'object_cache' => wp_using_ext_object_cache(),
            'content_dir_writable' => wp_is_writable(WP_CONTENT_DIR),
            'memory_limit' => (string) ini_get('memory_limit'),
        ],
    ];

    /** @var mixed $filtered */
    $filtered = apply_filters('vormbyte_site_profile', $profile);
    return $cached = is_array($filtered) ? $filtered : $profile;
}

/**
 * The site profile for agent-facing output that must never fail: an unusual site (or a missing
 * WordPress API) yields an empty profile instead of breaking the response.
 *
 * @return array<string, mixed>
 */
function vormbyte_site_profile_or_empty(): array
{
    try {
        return vormbyte_site_profile();
    } catch (\Throwable) {
        return [];
    }
}

/**
 * The site profile as Markdown lines for the agent instructions: facts first, then the editing
 * rules those facts imply.
 *
 * @return list<string>
 */
function vormbyte_site_profile_lines(): array
{
    try {
        $p = vormbyte_site_profile();
    } catch (\Throwable $e) {
        // Never let a detection problem on an unusual site break the connection.
        return ['## Site profile', '', 'Site profile unavailable: ' . $e->getMessage(), ''];
    }

    /** @var array<string, mixed> $site */
    $site = $p['site'];
    /** @var array<string, mixed> $theme */
    $theme = $p['theme'];
    /** @var array<string, mixed> $front */
    $front = $p['front'];
    /** @var list<array{key: string, label: string, posts: int, storage: string}> $builders */
    $builders = $p['builders'];
    /** @var array<string, list<string>> $plugins */
    $plugins = $p['plugins'];
    /** @var array<string, bool|string> $constraints */
    $constraints = $p['constraints'];

    $lines = ['## Site profile', ''];
    $lines[] = '- Site: ' . $site['name'] . ' — ' . $site['home_url'] . ' (environment: ' . $site['environment']
        . ($site['multisite'] ? ', multisite' : '') . ')';

    $theme_line = '- Theme: ' . $theme['name'] . ' ' . $theme['version'] . ' (`' . $theme['stylesheet'] . '`, '
        . ($theme['block_theme'] ? 'block theme / Site Editor' : 'classic theme') . ')';
    if ($theme['is_child']) {
        $theme_line .= ', child of ' . $theme['parent'];
    }
    $lines[] = $theme_line;

    if ($front['front_page'] !== null) {
        /** @var array{id: int, title: string} $fp */
        $fp = $front['front_page'];
        $lines[] = '- Homepage: static page "' . $fp['title'] . '" (ID ' . $fp['id'] . ')';
    } else {
        $lines[] = '- Homepage: latest posts';
    }

    foreach ($builders as $builder) {
        $lines[] = '- Page builder: ' . $builder['label'] . ' — used by ' . $builder['posts'] . ' post(s)/page(s)';
    }

    $concern_labels = [
        'seo' => 'SEO', 'cache' => 'Caching/optimization', 'commerce' => 'Commerce', 'fields' => 'Custom fields / data',
        'forms' => 'Forms', 'blocks' => 'Block libraries', 'builder_addons' => 'Builder add-ons', 'security' => 'Security',
        'backup' => 'Backups', 'editor' => 'Editor', 'snippets' => 'Code snippets', 'redirects' => 'Redirects',
        'smtp' => 'Email',
    ];
    foreach ($plugins as $concern => $labels) {
        $lines[] = '- ' . ($concern_labels[$concern] ?? $concern) . ': ' . implode(', ', $labels);
    }

    /** @var array<string, array{label: string, published: int, drafts: int}> $content */
    $content = $p['content'];
    if ($content !== []) {
        $parts = [];
        foreach ($content as $type => $c) {
            $parts[] = $c['label'] . ' (`' . $type . '`): ' . $c['published'];
        }
        $lines[] = '- Published content: ' . implode('; ', $parts);
    }

    /** @var array{menus: list<string>, locations: array<string, string>, assigned: array<string, int>} $menus */
    $menus = $p['menus'];
    if ($menus['menus'] !== [] || $menus['locations'] !== []) {
        $lines[] = '- Classic menus: ' . ($menus['menus'] !== [] ? implode(', ', $menus['menus']) : 'none')
            . '; theme locations: ' . ($menus['locations'] !== [] ? implode(', ', array_keys($menus['locations'])) : 'none');
    }
    /** @var list<string> $mu */
    $mu = $p['mu_plugins'];
    if ($mu !== []) {
        $lines[] = '- Must-use plugins: ' . implode(', ', $mu);
    }

    // Rules derived from what was detected. These are the decisions agents most often get wrong.
    $rules = [];

    if ($site['environment'] === 'production') {
        $rules[] = 'This site reports environment `production`. Real visitors see every change immediately: make the smallest change that does the job, prefer drafts/previews, and confirm before anything destructive or site-wide.';
    }
    if ($site['search_engines_discouraged']) {
        $rules[] = '"Discourage search engines" is on (blog_public = 0). Mention it if the user is preparing to launch; do not change it unasked.';
    }

    if ($theme['block_theme']) {
        /** @var array{templates: list<string>, parts: list<string>, global_styles: bool}|null $bc */
        $bc = $p['block_customizations'];
        $rule = 'Block theme: header, footer and page layouts are block templates (`wp_template`) and template parts (`wp_template_part`); colors, fonts and spacing come from theme.json plus user Global Styles (`wp_global_styles`). Edit through the Site Editor data, not PHP templates.';
        if ($bc !== null && ($bc['templates'] !== [] || $bc['parts'] !== [])) {
            $rule .= ' These are customized in the database and override the theme files, so edit the database copy: '
                . implode(', ', array_merge($bc['templates'], $bc['parts'])) . '.';
        }
        if ($bc !== null && $bc['global_styles']) {
            $rule .= ' User Global Styles are customized; style changes belong there, not in theme.json, or the user\'s customization will hide them.';
        }
        $rules[] = $rule;
    } else {
        $rules[] = 'Classic theme: layout comes from PHP template files (template hierarchy), header/footer from header.php/footer.php, menus from Appearance → Menus (`nav_menu_locations`), sidebars from widgets, and options from the Customizer (`get_theme_mods()`). Check theme_mods and the Customizer before editing template files.';
    }

    if (!$theme['is_child'] && !$theme['block_theme']) {
        $rules[] = 'The active theme is not a child theme: theme updates overwrite its files. Put PHP changes in a small plugin or must-use plugin (or the sandbox), CSS in Customizer → Additional CSS, and create a child theme only when templates must be overridden.';
    } elseif ($theme['is_child']) {
        $rules[] = 'A child theme is active (`' . $theme['stylesheet'] . '`): theme file changes go in the child theme, never in the parent `' . $theme['template'] . '`.';
    }
    if ($theme['customizer_css']) {
        $rules[] = 'Customizer → Additional CSS already holds custom CSS (`wp_get_custom_css()`): read it before adding styles so rules do not conflict or duplicate.';
    }

    foreach ($builders as $builder) {
        if ($builder['posts'] > 0) {
            $rules[] = $builder['label'] . ' owns ' . $builder['posts'] . ' page(s): for those, content is stored in ' . $builder['storage'] . ' Check each page\'s builder meta before editing; editing post_content of a builder page usually changes nothing visible.';
        } else {
            $rules[] = $builder['label'] . ' is active but no content uses it yet. Storage if used: ' . $builder['storage'];
        }
    }
    if (count(array_filter($builders, static fn(array $b): bool => $b['posts'] > 0)) > 1) {
        $rules[] = 'More than one page builder has content. Always determine the builder per page; never convert a page from one builder to another unless asked.';
    }

    if ($p['woocommerce'] !== null) {
        /** @var array{hpos: bool, shop_page: int, currency: string, block_cart_checkout: bool} $wc */
        $wc = $p['woocommerce'];
        $rules[] = 'WooCommerce: use its CRUD API (`wc_get_product()`, `$product->set_*()->save()`, `wc_get_orders()`), never raw post meta, so lookup tables, caches and hooks stay in sync.'
            . ($wc['hpos'] ? ' HPOS is on: orders live in `wc_orders` tables, not `wp_posts`.' : '')
            . ($wc['block_cart_checkout'] ? ' Checkout uses the Checkout block, so classic checkout hooks/templates do not apply.' : '');
    }
    if (isset($plugins['seo'])) {
        $rules[] = 'SEO titles and meta descriptions are managed by ' . implode(', ', $plugins['seo']) . '. Set them through that plugin\'s fields (e.g. Yoast `_yoast_wpseo_title`/`_yoast_wpseo_metadesc`, Rank Math `rank_math_title`/`rank_math_description`), not in theme code. When changing a published slug, add a redirect.';
    }
    if (isset($plugins['fields'])) {
        $rules[] = 'Custom fields come from ' . implode(', ', $plugins['fields']) . ': read and write them with that plugin\'s API (e.g. ACF `get_field()`/`update_field()` with the field key) so formatting and references stay valid.';
    }
    if (isset($plugins['cache']) || $constraints['page_cache_dropin']) {
        $rules[] = 'A page cache is active' . (isset($plugins['cache']) ? ' (' . implode(', ', $plugins['cache']) . ')' : '') . '. Purge it after front-end changes before judging whether a change worked, and test as a logged-out visitor.';
    }
    if (isset($plugins['snippets'])) {
        $rules[] = 'Custom code may live in ' . implode(', ', $plugins['snippets']) . ' snippets: search there before assuming a behaviour comes from the theme, and consider adding small snippets there when the user manages code that way.';
    }
    if (isset($plugins['editor'])) {
        $rules[] = 'Editor preference: ' . implode(', ', $plugins['editor']) . ' is active, so posts may use classic HTML content instead of blocks. Keep each post in the format it already uses.';
    }
    if (function_exists('vormbyte_get_active_languages')) {
        $multilingual = vormbyte_get_active_languages();
        if ($multilingual !== null && $multilingual['languages'] !== []) {
            $rules[] = 'Multilingual site (' . $multilingual['plugin'] . '): every translation is its own post/string. Find and update each language version, and never translate by overwriting the original.';
        }
    }
    if (!isset($plugins['backup'])) {
        $rules[] = 'No backup plugin was detected. Before risky or bulk changes, export what you change (e.g. `wp db export`, copy files) or ask the user to confirm a backup exists.';
    }
    if ($constraints['disallow_file_mods']) {
        $rules[] = 'DISALLOW_FILE_MODS is set: installing or updating plugins/themes through WordPress is blocked on purpose. Do not work around it; tell the user.';
    }
    if ($constraints['wp_debug'] === false) {
        $rules[] = 'WP_DEBUG is off. To diagnose errors, read the server error log or enable WP_DEBUG_LOG temporarily (with WP_DEBUG_DISPLAY false), and turn it back off afterwards.';
    }
    if (!$theme['writable']) {
        $rules[] = 'The theme directory is not writable by PHP: file edits there will fail. Use a plugin/mu-plugin or tell the user.';
    }

    if ($rules !== []) {
        $lines[] = '';
        $lines[] = '### What this means for edits on this site';
        $lines[] = '';
        foreach ($rules as $rule) {
            $lines[] = '- ' . $rule;
        }
    }

    $lines[] = '';
    return $lines;
}
