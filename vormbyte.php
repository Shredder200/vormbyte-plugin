<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Plugin Name: Vormbyte
 * Description: MCP server that lets AI agents understand and safely edit a WordPress site: site profiling, page-builder aware editing, PHP execution, WP-CLI and filesystem operations. For development and staging environments, with backups. Based on Novamira (AGPL-3.0).
 * Version: 1.12.10
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: Vormbyte
 * License: AGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain: vormbyte
 * Copyright: Ovation S.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/includes/compatibility.php';

define(constant_name: 'VORMBYTE_MAX_EXECUTION_TIME', value: 30);
define('VORMBYTE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORMBYTE_SANDBOX_DIR', WP_CONTENT_DIR . '/vormbyte-sandbox/');
define(constant_name: 'VORMBYTE_VENDOR_AUTOLOAD', value: __DIR__ . '/vendor/autoload_packages.php');
define(constant_name: 'VORMBYTE_MCP_AUTOLOAD', value: __DIR__ . '/vendor/vormbyte/mcp-adapter/autoload.php');
define(constant_name: 'VORMBYTE_MCP_ADAPTER_CLASS', value: 'Vormbyte\\Vendor\\WP\\MCP\\Core\\McpAdapter');

/**
 * Load bundled Composer dependencies and report the common source-ZIP install mistake clearly.
 *
 * @return WP_Error|null
 */
function vormbyte_load_bundled_dependencies()
{
    if (!file_exists(VORMBYTE_VENDOR_AUTOLOAD) || !file_exists(VORMBYTE_MCP_AUTOLOAD)) {
        return new WP_Error('vormbyte_missing_vendor', __(
            'This Vormbyte installation is incomplete, so AI clients cannot connect to this site. Install the built Vormbyte plugin ZIP (which includes the vendor/ folder), then reinstall it from Plugins → Add Plugin → Upload Plugin. Do not use a GitHub “Source code” ZIP.',
            domain: 'vormbyte',
        ));
    }

    try {
        require_once VORMBYTE_VENDOR_AUTOLOAD;
        require_once VORMBYTE_MCP_AUTOLOAD;
    } catch (\Throwable $e) {
        return new WP_Error('vormbyte_autoload_failed', sprintf(
            __(
                'Vormbyte could not load its required files, so AI clients cannot connect to this site. Reinstall Vormbyte using the built plugin ZIP, which includes the vendor/ folder. Technical error: %s',
                domain: 'vormbyte',
            ),
            $e->getMessage(),
        ));
    }

    if (!class_exists(VORMBYTE_MCP_ADAPTER_CLASS)) {
        return new WP_Error('vormbyte_mcp_adapter_missing', sprintf(
            __(
                'This Vormbyte installation is incomplete, so AI clients cannot connect to this site. Reinstall Vormbyte using the built plugin ZIP, which includes the vendor/ folder. Missing component: %s',
                domain: 'vormbyte',
            ),
            VORMBYTE_MCP_ADAPTER_CLASS,
        ));
    }

    return null;
}

/**
 * Store a runtime MCP dependency error.
 */
function vormbyte_set_mcp_dependency_error(WP_Error $error): void
{
    vormbyte_mcp_dependency_error($error);
}

/**
 * Return the current MCP dependency error, if any.
 *
 * @return WP_Error|null
 */
function vormbyte_get_mcp_dependency_error()
{
    return vormbyte_mcp_dependency_error();
}

/**
 * Shared storage for the current MCP dependency error.
 *
 * @return WP_Error|null
 */
function vormbyte_mcp_dependency_error(?WP_Error $error = null)
{
    static $current = null;

    if ($error !== null) {
        $current = $error;
    }

    return $current;
}

/**
 * Whether the bundled MCP Adapter is available for Vormbyte to initialize.
 */
function vormbyte_is_mcp_adapter_available(): bool
{
    return vormbyte_get_mcp_dependency_error() === null && class_exists(VORMBYTE_MCP_ADAPTER_CLASS);
}

/**
 * Show a persistent admin error when Vormbyte cannot expose MCP.
 */
function vormbyte_render_mcp_dependency_notice(): void
{
    if (!vormbyte_current_user_can_manage() || !vormbyte_admin_ui_visible()) {
        return;
    }

    $page = $_GET['page'] ?? null;
    if (
        is_string($page)
        && in_array(
            $page,
            [
                'vormbyte-connect',
                'vormbyte-connections',
                'vormbyte-abilities',
                'vormbyte-chat',
                'vormbyte-sandbox',
                'vormbyte-uninstall',
            ],
            strict: true,
        )
    ) {
        return;
    }

    $error = vormbyte_get_mcp_dependency_error();
    if ($error === null) {
        return;
    }

    wp_admin_notice(esc_html($error->get_error_message()), [
        'type' => 'error',
        'dismissible' => false,
    ]);
}

/**
 * Return a clear REST error at the MCP endpoint when the adapter cannot register its own route.
 */
function vormbyte_register_missing_mcp_endpoint(): void
{
    $error = vormbyte_get_mcp_dependency_error();
    if ($error === null) {
        return;
    }

    $routes = rest_get_server()->get_routes();
    $callback = static fn() => new WP_Error('vormbyte_mcp_adapter_unavailable', $error->get_error_message(), [
        'status' => 500,
    ]);

    foreach (['vormbyte', 'mcp-adapter-default-server'] as $route_slug) {
        if (array_key_exists('/mcp/' . $route_slug, $routes)) {
            continue;
        }
        register_rest_route('mcp', '/' . $route_slug, [
            'methods' => WP_REST_Server::ALLMETHODS,
            'callback' => $callback,
            'permission_callback' => '__return_true',
        ]);
    }
}

/**
 * Initialize the MCP Adapter and convert runtime failures into visible admin notices.
 */
function vormbyte_initialize_mcp_adapter(): bool
{
    if (!vormbyte_is_mcp_adapter_available()) {
        return false;
    }

    try {
        \Vormbyte\Vendor\WP\MCP\Core\McpAdapter::instance();
        return true;
    } catch (\Throwable $e) {
        vormbyte_set_mcp_dependency_error(
            new WP_Error('vormbyte_mcp_adapter_init_failed', sprintf(
                __(
                    'Vormbyte found the MCP Adapter, but it failed during initialization. Vormbyte will not register an MCP endpoint. Error: %s',
                    domain: 'vormbyte',
                ),
                $e->getMessage(),
            )),
        );
        return false;
    }
}

$vormbyte_dependency_error = vormbyte_load_bundled_dependencies();
if ($vormbyte_dependency_error !== null) {
    vormbyte_set_mcp_dependency_error($vormbyte_dependency_error);
}

require_once __DIR__ . '/includes/chat-schema.php';

register_activation_hook(__FILE__, callback: 'vormbyte_chat_schema_install');
register_deactivation_hook(__FILE__, callback: 'vormbyte_unschedule_gutenberg_cron');
register_activation_hook(__FILE__, callback: 'vormbyte_schedule_specializations_refresh');
register_deactivation_hook(__FILE__, callback: 'vormbyte_unschedule_specializations_refresh');
add_action('admin_notices', callback: 'vormbyte_render_mcp_dependency_notice');
add_action('network_admin_notices', callback: 'vormbyte_render_mcp_dependency_notice');
add_action('rest_api_init', callback: 'vormbyte_register_missing_mcp_endpoint', priority: 999);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/read-mode/policy.php';
require_once __DIR__ . '/includes/mcp-mirror-server.php';
require_once __DIR__ . '/includes/ghost-mode/bootstrap.php';
require_once __DIR__ . '/includes/design-authority.php';
require_once __DIR__ . '/includes/features/bootstrap.php';
require_once __DIR__ . '/includes/admin-notices.php';
require_once __DIR__ . '/includes/abilities/bootstrap.php';
require_once __DIR__ . '/includes/updater.php';
require_once __DIR__ . '/includes/admin-page.php';
require_once __DIR__ . '/includes/uninstall-review.php';
register_activation_hook(__FILE__, callback: 'vormbyte_clear_uninstall_plan');
require_once __DIR__ . '/includes/hosting/detector.php';
require_once __DIR__ . '/includes/connect-page.php';
require_once __DIR__ . '/includes/oauth/connections.php';
add_action('admin_menu', callback: static function (): void {
    \Vormbyte\OAuth\Connections\register();
});
require_once __DIR__ . '/includes/specializations.php';
require_once __DIR__ . '/includes/pro-upsell.php';
require_once __DIR__ . '/includes/upload-link.php';
require_once __DIR__ . '/includes/admin-access-link.php';
require_once __DIR__ . '/includes/skills/bootstrap.php';
require_once __DIR__ . '/includes/oauth/bootstrap.php';
require_once __DIR__ . '/includes/cache-purge.php';
\Vormbyte\CachePurge\register();
require_once __DIR__ . '/includes/troubleshoot/bootstrap.php';
require_once __DIR__ . '/includes/instructions-admin.php';

\Vormbyte\Context\boot_context_admin();
vormbyte_register_wordpress_compatibility_notice();
vormbyte_boot_ability_rest_surface();
vormbyte_register_ability_policy_hook();
vormbyte_register_read_mode_hooks();

add_action('admin_post_vormbyte_toggle_ai_abilities', callback: 'vormbyte_handle_admin_bar_toggle');
add_action('admin_post_vormbyte_download_mcpb', callback: 'vormbyte_handle_download_mcpb');

function vormbyte_unschedule_gutenberg_cron(): void
{
    require_once __DIR__ . '/includes/abilities/gutenberg/bootstrap.php';
    \Vormbyte\Abilities\Gutenberg\unschedule_cleanup();
}

function vormbyte_boot_design_feature(): void
{
    require_once __DIR__ . '/includes/design/bootstrap.php';
}

function vormbyte_boot_visual_feature(): void
{
    require_once __DIR__ . '/vormbyte-visual/bootstrap.php';
}

function vormbyte_boot_chat_feature(): void
{
    vormbyte_chat_schema_maybe_install();
    require_once __DIR__ . '/includes/chat.php';
}

function vormbyte_boot_block_editor_queue_feature(): void
{
    if (vormbyte_is_enabled() && vormbyte_wordpress_abilities_supported()) {
        vormbyte_load_gutenberg_runtime();
        add_action(
            'wp_abilities_api_categories_init',
            callback: 'vormbyte_register_gutenberg_ability_category',
            priority: 20,
        );
        add_action('wp_abilities_api_init', callback: 'vormbyte_load_gutenberg_abilities', priority: 20);
    }
}

function vormbyte_deactivate_block_editor_queue_feature(): void
{
    require_once __DIR__ . '/includes/abilities/gutenberg/bootstrap.php';
    \Vormbyte\Abilities\Gutenberg\unschedule_cleanup();
}

function vormbyte_load_gutenberg_runtime(): void
{
    require_once __DIR__ . '/includes/abilities/gutenberg/bootstrap.php';
    require_once __DIR__ . '/includes/abilities/gutenberg/runtime.php';
    require_once __DIR__ . '/includes/abilities/gutenberg/rest.php';
    require_once __DIR__ . '/includes/gutenberg-finalizer-admin.php';
    \Vormbyte\GutenbergFinalizer\boot_gutenberg_finalizer_admin();
}

function vormbyte_load_gutenberg_abilities(): void
{
    $gutenberg_dir = __DIR__ . '/includes/abilities/gutenberg/';
    require_once $gutenberg_dir . 'bootstrap.php';
    require_once $gutenberg_dir . 'runtime.php';
    require_once $gutenberg_dir . 'get-finalizer-runtime.php';
    require_once $gutenberg_dir . 'list-block-types.php';
    require_once $gutenberg_dir . 'get-block-type.php';
    require_once $gutenberg_dir . 'get-content.php';
    require_once $gutenberg_dir . 'write-content.php';
    require_once $gutenberg_dir . 'create-pending-batch.php';
    require_once $gutenberg_dir . 'add-pending-change.php';
    require_once $gutenberg_dir . 'enable-batch-finalization.php';
    require_once $gutenberg_dir . 'get-pending-batch.php';
    require_once $gutenberg_dir . 'list-pending-batches.php';
    require_once $gutenberg_dir . 'delete-pending-batch.php';
    require_once $gutenberg_dir . 'delete-pending-change.php';
    require_once $gutenberg_dir . 'get-finalization-url.php';
}

function vormbyte_inject_custom_instructions(mixed $instructions): mixed
{
    if (!is_string($instructions)) {
        return $instructions;
    }

    // Stay out while a Vormbyte Pro that still manages custom instructions is
    // active: it injects its own copy (priority 5), so the base must not add a
    // second one. The base takes over once that Pro is gone or updated.
    if (\Vormbyte\Context\legacy_pro_context_loaded()) {
        return $instructions;
    }

    if (\Vormbyte\Context\instructions_custom_injection_suppressed()) {
        return $instructions;
    }

    if (!\Vormbyte\Context\instructions_is_enabled()) {
        return $instructions;
    }

    $custom = \Vormbyte\Context\instructions_get_content();
    if (trim($custom) === '') {
        return $instructions;
    }

    if (str_starts_with($instructions, $custom . "\n\n")) {
        return $instructions;
    }

    return $custom . "\n\n" . $instructions;
}

add_filter('vormbyte_discover_abilities_instructions', callback: 'vormbyte_inject_custom_instructions', priority: 6);

/**
 * Add the Vormbyte AI Abilities status and toggle to the WordPress admin bar.
 */
function vormbyte_register_admin_bar_toggle(\WP_Admin_Bar $wp_admin_bar): void
{
    if (!vormbyte_current_user_can_manage() || !vormbyte_admin_ui_visible()) {
        return;
    }

    $dependency_error = vormbyte_get_mcp_dependency_error();
    $configured_enabled = vormbyte_is_enabled();
    $active = $configured_enabled && $dependency_error === null;
    $can_enable = $configured_enabled || $dependency_error === null;
    $target = $configured_enabled ? 'off' : 'on';
    $toggle_url = wp_nonce_url(
        admin_url('admin-post.php?action=vormbyte_toggle_ai_abilities&vormbyte_target=' . $target),
        action: 'vormbyte_toggle_ai_abilities',
    );

    $wp_admin_bar->add_node(\Vormbyte\ReadMode\admin_bar_node([
        'id' => 'vormbyte-mcp-status',
        'title' => match (true) {
            $active => esc_html__('Vormbyte ON', domain: 'vormbyte'),
            $configured_enabled => esc_html__('Vormbyte ERROR', domain: 'vormbyte'),
            default => esc_html__('Vormbyte', domain: 'vormbyte'),
        },
        'href' => admin_url('admin.php?page=vormbyte-connect'),
        'meta' => [
            'class' => match (true) {
                $active => 'vormbyte-mcp-on',
                $configured_enabled => 'vormbyte-mcp-error',
                default => 'vormbyte-mcp-off',
            },
        ],
    ]));

    $wp_admin_bar->add_node([
        'id' => 'vormbyte-mcp-status-label',
        'parent' => 'vormbyte-mcp-status',
        'title' => match (true) {
            $active => esc_html__('AI Abilities: On', domain: 'vormbyte'),
            $configured_enabled => esc_html__('AI Abilities: Error', domain: 'vormbyte'),
            default => esc_html__('AI Abilities: Off', domain: 'vormbyte'),
        },
    ]);

    if (!$can_enable) {
        $wp_admin_bar->add_node([
            'id' => 'vormbyte-mcp-unavailable',
            'parent' => 'vormbyte-mcp-status',
            'title' => esc_html__('AI Abilities unavailable', domain: 'vormbyte'),
            'href' => admin_url('admin.php?page=vormbyte-connect'),
        ]);
    }

    if ($can_enable) {
        $wp_admin_bar->add_node([
            'id' => 'vormbyte-mcp-toggle',
            'parent' => 'vormbyte-mcp-status',
            'title' => $configured_enabled
                ? esc_html__('Turn Off AI Abilities', domain: 'vormbyte')
                : esc_html__('Turn On AI Abilities', domain: 'vormbyte'),
            'href' => $toggle_url,
            'meta' => [
                'class' => $configured_enabled ? 'vormbyte-mcp-toggle-off' : 'vormbyte-mcp-toggle-on',
            ],
        ]);
    }

    $wp_admin_bar->add_node([
        'id' => 'vormbyte-mcp-config',
        'parent' => 'vormbyte-mcp-status',
        'title' => esc_html__('Configuration', domain: 'vormbyte'),
        'href' => admin_url('admin.php?page=vormbyte-connect'),
    ]);
}

/**
 * Style the admin-bar status chip and require confirmation before enabling from the dropdown.
 */
function vormbyte_render_admin_bar_toggle_assets(): void
{
    if (!vormbyte_current_user_can_manage() || !vormbyte_admin_ui_visible() || !is_admin_bar_showing()) {
        return;
    }

    $looks_production = vormbyte_looks_like_production();
    $confirm_message = $looks_production
        ? __(
            'This looks like a production site. AI Abilities are intended for staging or development sites. Continue anyway?',
            domain: 'vormbyte',
        )
        : __('AI agents will be able to execute PHP code and access the filesystem. Continue?', domain: 'vormbyte');
    ?>
    <style>
    #wp-admin-bar-vormbyte-mcp-status.vormbyte-mcp-on > .ab-item {
        background: #c00 !important;
        color: #fff !important;
    }
    #wp-admin-bar-vormbyte-mcp-status.vormbyte-mcp-error > .ab-item {
        background: #996800 !important;
        color: #fff !important;
    }
    #wp-admin-bar-vormbyte-mcp-status-label > .ab-item {
        cursor: default;
        font-weight: 600;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('#wp-admin-bar-vormbyte-mcp-toggle.vormbyte-mcp-toggle-on > .ab-item');
        if (!toggle) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            if (!window.confirm(<?php echo wp_json_encode($confirm_message); ?>)) {
                event.preventDefault();
            }
        });
    });
    </script>
    <?php
}

add_action('admin_bar_menu', callback: 'vormbyte_register_admin_bar_toggle', priority: 999);
add_action('admin_head', callback: 'vormbyte_render_admin_bar_toggle_assets');
add_action('wp_head', callback: 'vormbyte_render_admin_bar_toggle_assets');

// Optional dev mock for the external-skills source. Gitignored. Loaded
// only when the constant is set (e.g. in wp-config.php) so it never ships
// to production builds.
if (
    defined('VORMBYTE_DEV_MOCK_PRO')
    && constant('VORMBYTE_DEV_MOCK_PRO') === true
    && file_exists(__DIR__ . '/includes/skills/dev-mock.php')
) {
    require_once __DIR__ . '/includes/skills/dev-mock.php';
}

// Suppress noisy admin notices on the Configuration page via CSS: hide notices that are not
// emitted by Vormbyte or Vormbyte Pro. Cheap and side-effect free, unlike iterating $wp_filter
// with Reflection (which causes memory blow-ups when Query Monitor captures every remove_action).
add_action('admin_head', static function () {
    if (($_GET['page'] ?? null) !== 'vormbyte-connect') {
        return;
    }
    ?>
    <style id="vormbyte-suppress-foreign-notices">
        .wrap > .notice:not(.vormbyte-pro-notice):not(.vormbyte-keep),
        #wpbody-content > .notice:not(.vormbyte-pro-notice):not(.vormbyte-keep),
        #wpbody-content > .updated:not(.vormbyte-keep),
        #wpbody-content > .error:not(.vormbyte-keep) {
            display: none !important;
        }
    </style>
    <?php
});

// Handle form actions early (before headers are sent) for PRG redirect.
add_action('admin_init', static function () {
    $page = $_GET['page'] ?? null;
    if ($page === 'vormbyte-sandbox') {
        vormbyte_handle_sandbox_actions();
    }
    if ($page === 'vormbyte-connect') {
        vormbyte_handle_revoke_password();
        vormbyte_handle_dismiss_production_warning();
    }
    if ($page === 'vormbyte-abilities') {
        vormbyte_handle_ability_hub_actions();
    }
});

// Single-row toggle over AJAX so the page state (open sections) is preserved.
add_action('wp_ajax_vormbyte_toggle_ability', callback: 'vormbyte_handle_ability_toggle_ajax');

// Admin page stylesheets — card layouts matching Skills.
add_action('admin_enqueue_scripts', static function (string $hook): void {
    $debug_assets = defined('WP_DEBUG') && constant('WP_DEBUG') === true;
    $asset_version = static fn(string $path): string => $debug_assets && is_file($path)
        ? (string) filemtime($path)
        : VORMBYTE_VERSION;

    if (in_array($hook, ['vormbyte_page_vormbyte-abilities', 'vormbyte_page_vormbyte-sandbox'], strict: true)) {
        wp_enqueue_style(
            'vormbyte-admin-list',
            (string) VORMBYTE_PLUGIN_URL . 'includes/assets/admin-list.css',
            [],
            $asset_version(__DIR__ . '/includes/assets/admin-list.css'),
        );
    }

    if ($hook === 'vormbyte_page_vormbyte-abilities') {
        wp_enqueue_style(
            'vormbyte-hub-admin',
            (string) VORMBYTE_PLUGIN_URL . 'includes/assets/hub.css',
            ['vormbyte-admin-list'],
            $asset_version(__DIR__ . '/includes/assets/hub.css'),
        );
        wp_enqueue_script(
            'vormbyte-hub-admin',
            (string) VORMBYTE_PLUGIN_URL . 'includes/assets/hub.js',
            [],
            $asset_version(__DIR__ . '/includes/assets/hub.js'),
            args: true,
        );
    }

    if ($hook === 'vormbyte_page_vormbyte-sandbox') {
        wp_enqueue_style(
            'vormbyte-sandbox-admin',
            (string) VORMBYTE_PLUGIN_URL . 'includes/assets/sandbox.css',
            ['vormbyte-admin-list'],
            $asset_version(__DIR__ . '/includes/assets/sandbox.css'),
        );
    }
});

// Register admin menus.
// Menu order uses spaced admin_menu priorities (multiples of 10) so entries
// can be positioned without post-hoc reordering.
add_action(
    'admin_menu',
    static function () {
        // Top-level menu item (shows the Connect page).
        add_menu_page(
            page_title: __('Configuration', domain: 'vormbyte'),
            menu_title: 'Vormbyte',
            capability: vormbyte_manage_capability(),
            menu_slug: 'vormbyte-connect',
            callback: 'vormbyte_render_connect_page',
            // Official Vormbyte N, encoded so WordPress applies its native SVG menu sizing.
            icon_url: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIj48cGF0aCBkPSJNMTUgNC41SDcuNWEzIDMgMCAwIDAgMCA2aDVhMyAzIDAgMCAxIDAgNkg1IiBmaWxsPSJub25lIiBzdHJva2U9IiNmZmYiIHN0cm9rZS13aWR0aD0iMi42IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz48L3N2Zz4=',
            position: 3,
        );

        // Rename the auto-created first submenu entry to match the page title.
        add_submenu_page(
            parent_slug: 'vormbyte-connect',
            page_title: __('Configuration', domain: 'vormbyte'),
            menu_title: __('Configuration', domain: 'vormbyte'),
            capability: vormbyte_manage_capability(),
            menu_slug: 'vormbyte-connect',
            callback: 'vormbyte_render_connect_page',
        );
    },
    priority: 10,
);

// Abilities Hub — priority 25 places it after Troubleshoot (20) rather than in the priority-10
// Configuration group, so the connection diagnostics sit directly below Connect. (30 is taken by
// Context, so 25 keeps it between Troubleshoot and Context without renumbering the rest.)
add_action(
    'admin_menu',
    static function () {
        add_submenu_page(
            parent_slug: 'vormbyte-connect',
            page_title: __('Abilities Hub', domain: 'vormbyte'),
            menu_title: __('Abilities Hub', domain: 'vormbyte'),
            capability: vormbyte_manage_capability(),
            menu_slug: 'vormbyte-abilities',
            callback: 'vormbyte_render_settings_page',
        );
    },
    priority: 25,
);

// Sandbox sub-page — priority 50 places it after Context (30) and Skills (40).
add_action(
    'admin_menu',
    static function () {
        add_submenu_page(
            parent_slug: 'vormbyte-connect',
            page_title: __('Sandbox', domain: 'vormbyte'),
            menu_title: __('Sandbox', domain: 'vormbyte'),
            capability: vormbyte_manage_capability(),
            menu_slug: 'vormbyte-sandbox',
            callback: 'vormbyte_render_sandbox_page',
        );
    },
    priority: 50,
);

$is_enabled = vormbyte_is_enabled();

if (!$is_enabled && vormbyte_is_domain_mismatch()) {
    add_action('admin_notices', static function () {
        if (!vormbyte_current_user_can_manage() || !vormbyte_admin_ui_visible()) {
            return;
        }
        /** @var string $locked */
        $locked = get_option('vormbyte_ai_abilities_domain', default_value: '');
        vormbyte_render_persistent_admin_notice(
            sprintf(
                esc_html__(
                    'Vormbyte AI Abilities were disabled because the site domain changed (enabled on %s). Re-enable them from the Configuration page if this is intentional.',
                    domain: 'vormbyte',
                ),
                '<code>' . esc_html($locked) . '</code>',
            ),
            meta_key: 'vormbyte_domain_mismatch_notice_dismissed',
            dismiss_value: md5($locked),
            args: ['type' => 'warning', 'additional_classes' => ['vormbyte-keep']],
        );
    });
}

$vormbyte_abilities_supported = vormbyte_wordpress_abilities_supported();
$vormbyte_adapter_initialized = false;

if ($is_enabled && $vormbyte_abilities_supported) {
    vormbyte_register_ability_hooks();

    // MCP clients commonly leave sessions behind when they disconnect. Keep enough short-lived
    // sessions to avoid the adapter evicting active sessions when its default 32-session cap is reached.
    add_filter('vormbyte_mcp_adapter_session_max_per_user', static fn(): int => 128);
    add_filter('vormbyte_mcp_adapter_session_inactivity_timeout', static fn(): int => 4 * HOUR_IN_SECONDS);

    // Brand the default MCP server. Usage instructions are returned from the
    // discover-abilities tool instead of the initialize handshake.
    add_filter(
        'vormbyte_mcp_adapter_tool_name',
        static function (string $tool_name, mixed $ability): string {
            if (!$ability instanceof \WP_Ability) {
                return $tool_name;
            }
            return match ($ability->get_name()) {
                'vormbyte-mcp-adapter/discover-abilities' => 'mcp-adapter-discover-abilities',
                'vormbyte-mcp-adapter/get-ability-info' => 'mcp-adapter-get-ability-info',
                'vormbyte-mcp-adapter/execute-ability' => 'mcp-adapter-execute-ability',
                default => $tool_name,
            };
        },
        accepted_args: 2,
    );

    add_filter('vormbyte_mcp_adapter_default_server_config', static function (mixed $config): mixed {
        if (!is_array($config)) {
            return $config;
        }
        $config['server_id'] = 'vormbyte';
        $config['server_route'] = 'vormbyte';
        $config['server_name'] = 'Vormbyte';
        return $config;
    });

    // Register a legacy alias server at the old slug so configs that still point at
    // /wp-json/mcp/mcp-adapter-default-server keep working after the rename.
    add_action('vormbyte_mcp_adapter_init', callback: 'vormbyte_register_legacy_mcp_server', priority: 20);

    // Register the OAuth-only server at /mcp/vormbyte-oauth. Keeping the OAuth Bearer flow on a
    // route of its own means the OAuth middleware never touches the canonical /mcp/vormbyte
    // endpoint that the existing Application Password installs use. Gated on the same transport
    // check as the OAuth bootstrap so the endpoint never exists without its token/authorize peers.
    add_action('vormbyte_mcp_adapter_init', callback: 'vormbyte_register_oauth_mcp_server', priority: 20);

    // Initialize the optional bundled adapter after the transport-neutral Ability and REST hooks.
    // An adapter failure must not remove those hooks or make the REST Ability surface disappear.
    $vormbyte_adapter_initialized = vormbyte_initialize_mcp_adapter();
}

/**
 * Register a legacy alias of the canonical Vormbyte MCP server at the pre-rename slug.
 *
 * The canonical server is registered under `/mcp/vormbyte`. Older client configs may still
 * point at `/mcp/mcp-adapter-default-server` from before the rename — this alias keeps them
 * working with identical behavior (same tools, same auto-discovered resources and prompts).
 */
function vormbyte_register_legacy_mcp_server(mixed $adapter): void
{
    if (!$adapter instanceof \Vormbyte\Vendor\WP\MCP\Core\McpAdapter) {
        return;
    }

    if ($adapter->get_server('vormbyte') === null) {
        return;
    }

    vormbyte_create_mirror_mcp_server(
        $adapter,
        server_id: 'mcp-adapter-default-server',
        route: 'mcp-adapter-default-server',
        name: 'Vormbyte (legacy alias)',
        description: 'Legacy alias for the Vormbyte MCP server. New client configurations should use /wp-json/mcp/vormbyte.',
    );
}

/**
 * Register the OAuth-authenticated Vormbyte MCP server at `/mcp/vormbyte-oauth`.
 *
 * The OAuth Bearer flow lives on this dedicated route so the canonical `/mcp/vormbyte` endpoint —
 * used by the existing Application Password installs — is never seen by the OAuth challenge
 * middleware (see includes/oauth/middleware.php::is_mcp_route). Registered only when the OAuth
 * transport is permitted, mirroring includes/oauth/bootstrap.php so the endpoint never exists
 * without the token/authorize endpoints that make it usable.
 */
function vormbyte_register_oauth_mcp_server(mixed $adapter): void
{
    if (!$adapter instanceof \Vormbyte\Vendor\WP\MCP\Core\McpAdapter) {
        return;
    }

    if (!vormbyte_oauth_transport_allowed()) {
        return;
    }

    if ($adapter->get_server('vormbyte') === null) {
        return;
    }

    vormbyte_create_mirror_mcp_server(
        $adapter,
        server_id: 'vormbyte-oauth',
        route: 'vormbyte-oauth',
        name: 'Vormbyte (OAuth)',
        description: 'OAuth-authenticated Vormbyte MCP endpoint. Application Password clients use /wp-json/mcp/vormbyte.',
    );
}

if ($vormbyte_adapter_initialized) {
    // The `vormbyte-mcp-adapter/execute-ability` dispatcher wraps every ability return in
    // `{ success: true, data: <inner> }`. When the inner value is itself
    // `{ success: false, error: "..." }` the outer `success: true` masks a real
    // logical failure, and agents that check the top-level flag — a very
    // reasonable default — silently march past the error. Unwrap that shape
    // here so the adapter's backward-compat path (ToolsHandler) turns it into a
    // proper `isError: true` CallToolResult.
    //
    // ToolsHandler::create_error_result flattens the response to a bare
    // `content: [text(error)], structuredContent: null, isError: true` — every
    // sibling field on the ability's return is discarded. Validators attach
    // structured repair hints (`invalid_values`, `unknown_properties`,
    // `collision_paths`, `suggested_name`, `failed_paths`, `overwritten_paths`,
    // `errors`, `schemas`, `style_errors`, `dynamic_tag_errors`, `dropped_keys`,
    // `schema`, …) that the agent needs to self-correct without a
    // round-trip — so embed whatever else the ability returned as a JSON
    // suffix on the error message. The suffix rides inside the string and
    // survives the downstream flatten.
    add_filter(
        'vormbyte_mcp_adapter_tool_call_result',
        static function (mixed $result, array $args, string $tool_name): mixed {
            // Tool names are MCP-sanitized from ability slugs — `/` becomes `-`.
            if ($tool_name !== 'mcp-adapter-execute-ability') {
                return $result;
            }
            if (!is_array($result) || ($result['success'] ?? null) !== true) {
                return $result;
            }
            /** @var array<array-key, mixed>|null $data */
            $data = $result['data'] ?? null;
            if (!is_array($data) || ($data['success'] ?? null) !== false) {
                return $result;
            }
            /** @var string|null $error */
            $error = $data['error'] ?? null;
            if (!is_string($error) || trim($error) === '') {
                return $result;
            }

            $detail = $data;
            unset($detail['success'], $detail['error']);
            if ($detail !== []) {
                $encoded = wp_json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (is_string($encoded)) {
                    $data['error'] = $error . "\n\nStructured detail (JSON):\n" . $encoded;
                }
            }

            return $data;
        },
        priority: 10,
        accepted_args: 3,
    );

    // Fix empty "properties" in JSON Schema: PHP json_encode outputs [] instead of {}.
    // MCP clients reject tools with invalid schemas, so we fix this in the REST response.
    add_filter('rest_pre_echo_response', static function (mixed $result): mixed {
        if (!is_array($result)) {
            return $result;
        }
        /** @var \stdClass|null $resultObj */
        $resultObj = $result['result'] ?? null;
        if (!$resultObj instanceof \stdClass) {
            return $result;
        }
        /** @var list<array<string, mixed>>|null $tools */
        $tools = $resultObj->tools ?? null;
        if (!is_array($tools)) {
            return $result;
        }
        foreach ($tools as &$tool) {
            foreach (['inputSchema', 'outputSchema'] as $key) {
                /** @var array<string, mixed>|null $schema */
                $schema = $tool[$key] ?? null;
                if (!is_array($schema)) {
                    continue;
                }
                $tool[$key] = vormbyte_normalize_empty_schema_properties($schema);
            }
        }
        $resultObj->tools = $tools;
        return $result;
    });
}
add_filter(
    'vormbyte_mcp_adapter_tool_call_result',
    callback: 'vormbyte_enrich_unavailable_wp_cli_error',
    priority: 5,
    accepted_args: 2,
);
add_filter(
    'vormbyte_mcp_adapter_tool_call_result',
    callback: 'vormbyte_enrich_disabled_ability_error',
    priority: 10,
    accepted_args: 2,
);

// Ensure the sandbox exists, cannot be served directly, and uses safe disabled-file markers.
vormbyte_prepare_sandbox_directory();

// Load sandbox plugins.
require_once __DIR__ . '/includes/sandbox-loader.php';
