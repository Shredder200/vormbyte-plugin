<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace VormbyteVisual;

if (!defined('ABSPATH')) {
    exit();
}

\add_action('template_redirect', __NAMESPACE__ . '\\redirect_workspace_path');

function redirect_workspace_path(): void
{
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if ($request_uri === '') {
        return;
    }

    /** @var mixed $path */
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($path) || trim($path, characters: '/') !== 'wp-admin/vormbyte-visual-workspace') {
        return;
    }

    $workspace_url = \admin_url('admin-post.php?action=vormbyte-visual');
    if (!\is_user_logged_in()) {
        \wp_safe_redirect(\wp_login_url($workspace_url));
        exit();
    }

    if (!\vormbyte_current_user_can_manage()) {
        \wp_die(
            message: \esc_html__('You do not have permission to access Vormbyte Visual.', domain: 'vormbyte'),
            title: \esc_html__('Vormbyte Visual', domain: 'vormbyte'),
            args: ['response' => 403],
        );
    }

    \wp_safe_redirect($workspace_url);
    exit();
}

class Workspace
{
    public function __construct()
    {
        \add_action('admin_menu', [$this, 'add_menu'], priority: 80);
        \add_action('admin_post_vormbyte-visual', [$this, 'render_standalone_page']);
        \add_action('admin_post_nopriv_vormbyte-visual', [$this, 'redirect_to_login']);
        \add_action('wp_ajax_vormbyte_visual_auth_check', [$this, 'ajax_auth_check']);
        \add_action('wp_ajax_nopriv_vormbyte_visual_auth_check', [$this, 'ajax_auth_check']);
        \add_action('wp_ajax_vormbyte_visual_set_abilities', [$this, 'ajax_set_abilities']);
        \add_action('admin_post_vormbyte_visual_download_mcpb', [$this, 'handle_download_mcpb']);
    }

    public function ajax_auth_check(): void
    {
        $logged_in = \is_user_logged_in();
        $can_manage = $logged_in && \vormbyte_current_user_can_manage();
        $workspace_url = $this->get_workspace_url();
        $read_mode = self::read_mode_active();

        \wp_send_json_success([
            'authenticated' => $can_manage && \vormbyte_admin_ui_visible(),
            'loggedIn' => $logged_in,
            'canManage' => $can_manage,
            'recoverUrl' => $logged_in ? $workspace_url : \wp_login_url($workspace_url),
            // A workspace that was open before read mode was turned on learns it here and stops.
            'readMode' => $read_mode,
            'readModeMessage' => $read_mode ? \vormbyte_read_mode_blocked_message('Vormbyte Visual') : '',
        ]);
    }

    public function ajax_set_abilities(): void
    {
        \check_ajax_referer(action: 'wp_rest', query_arg: 'nonce');
        if (!\vormbyte_current_user_can_manage()) {
            \wp_send_json_error([
                'message' => \__('You do not have permission to change AI Abilities.', domain: 'vormbyte'),
            ], status_code: 403);
        }

        $raw = file_get_contents('php://input');
        /** @var mixed $params */
        $params = is_string($raw) && $raw !== '' ? json_decode($raw, associative: true) : [];
        $enable = is_array($params) && ($params['enabled'] ?? false) === true;

        $toggle = $enable ? 'vormbyte_enable_ai_abilities' : 'vormbyte_disable_ai_abilities';
        if (function_exists($toggle)) {
            \call_user_func($toggle);
        }

        \wp_send_json_success(['enabled' => function_exists('vormbyte_is_enabled') && \vormbyte_is_enabled()]);
    }

    public function add_menu(): void
    {
        if (!defined('VORMBYTE_VERSION')) {
            return;
        }

        $hook = \add_submenu_page(
            parent_slug: 'vormbyte-connect',
            page_title: \__('Vormbyte Visual', domain: 'vormbyte'),
            menu_title: \sprintf(
                '%s <span class="awaiting-mod">%s</span>',
                \esc_html__('Visual', domain: 'vormbyte'),
                \esc_html__('Experimental', domain: 'vormbyte'),
            ),
            capability: \vormbyte_manage_capability(),
            menu_slug: 'vormbyte-visual',
            callback: [$this, 'render_page'],
        );

        if ($hook === false) {
            return;
        }

        \add_action('load-' . $hook, [$this, 'redirect_to_standalone_page']);
    }

    public function redirect_to_standalone_page(): void
    {
        if (!\vormbyte_current_user_can_manage()) {
            return;
        }

        \wp_safe_redirect($this->get_workspace_url());
        exit();
    }

    public function render_page(): void
    {
        if (!\vormbyte_current_user_can_manage()) {
            return;
        }
        ?>
        <div class="wrap">
            <p>
                <a href="<?php echo \esc_url($this->get_workspace_url()); ?>" class="button button-primary">
                    <?php echo \esc_html__('Open Vormbyte Visual', domain: 'vormbyte'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    public function render_standalone_page(): void
    {
        if (!\vormbyte_current_user_can_manage()) {
            \wp_die(
                message: \esc_html__('You do not have permission to access Vormbyte Visual.', domain: 'vormbyte'),
                title: \esc_html__('Vormbyte Visual', domain: 'vormbyte'),
                args: ['response' => 403],
            );
        }

        // Vormbyte Visual does not work while read mode is on: the workspace is not served, and the page says why.
        if (self::read_mode_active()) {
            $this->render_read_mode_page();

            exit();
        }

        $this->enqueue_workspace_assets();
        \wp_enqueue_style('buttons');

        ?>
        <!doctype html>
        <html <?php \language_attributes(); ?> class="vormbyte-visual-workspace-document">
        <head>
            <meta charset="<?php \bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo \esc_html__('Vormbyte Visual', domain: 'vormbyte'); ?></title>
            <?php \wp_print_styles(); ?>
        </head>
        <body class="vormbyte-visual-workspace-document">
            <div id="vormbyte-visual-workspace-root"></div>
            <?php \wp_print_footer_scripts(); ?>
        </body>
        </html>
        <?php

        exit();
    }

    /** Whether Vormbyte read mode is on. */
    private static function read_mode_active(): bool
    {
        return (
            function_exists('vormbyte_is_read_mode')
            && function_exists('vormbyte_read_mode_blocked_message')
            && \vormbyte_is_read_mode()
        );
    }

    /** What the workspace page shows instead of the workspace while read mode is on. */
    public function render_read_mode_page(): void
    {
        \wp_enqueue_style('buttons');
        $read_mode_url = function_exists('Vormbyte\\ReadMode\\page_url') ? \Vormbyte\ReadMode\page_url() : '';

        ?>
        <!doctype html>
        <html <?php \language_attributes(); ?> class="vormbyte-visual-workspace-document">
        <head>
            <meta charset="<?php \bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo \esc_html__('Vormbyte Visual', domain: 'vormbyte'); ?></title>
            <?php \wp_print_styles(); ?>
        </head>
        <body class="vormbyte-visual-workspace-document" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:560px;margin:15vh auto;padding:0 24px;">
            <h1 style="font-size:20px;font-weight:600;"><?php echo \esc_html__('Vormbyte Visual is unavailable', domain: 'vormbyte'); ?></h1>
            <p><?php echo \esc_html__(
                'Read Mode is on, so Vormbyte Visual does not work. Turn off Read Mode to use it again.',
                domain: 'vormbyte',
            ); ?></p>
            <?php if ($read_mode_url !== ''): ?>
                <p><a href="<?php echo \esc_url($read_mode_url); ?>" class="button button-primary"><?php echo \esc_html__(
                    'Go to Read Mode',
                    domain: 'vormbyte',
                ); ?></a></p>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }

    public function redirect_to_login(): void
    {
        /** @var array<string, mixed> $request_params */
        $request_params = \wp_unslash($_GET);
        $redirect_to = \add_query_arg($request_params, \admin_url('admin-post.php'));

        \wp_safe_redirect(\wp_login_url($redirect_to));
        exit();
    }

    private function enqueue_workspace_assets(): void
    {
        $asset_file = VORMBYTE_VISUAL_PLUGIN_DIR . 'dist/workspace.js';
        $version = \Vormbyte\Visual\asset_version($asset_file);

        \wp_enqueue_script(
            'vormbyte-visual-workspace',
            VORMBYTE_VISUAL_PLUGIN_URL . 'dist/workspace.js',
            [],
            $version,
            args: true,
        );

        $abilities_enabled = function_exists('vormbyte_is_enabled') && \vormbyte_is_enabled();
        $looks_production = function_exists('vormbyte_looks_like_production') && \vormbyte_looks_like_production();
        $read_mode =
            function_exists('vormbyte_is_read_mode')
            && function_exists('vormbyte_read_mode_blocked_message')
            && \vormbyte_is_read_mode();

        \wp_localize_script('vormbyte-visual-workspace', object_name: 'vormbyteVisualWorkspaceData', l10n: [
            'adminUrl' => \esc_url_raw(\admin_url()),
            'siteUrl' => \esc_url_raw(\home_url('/')),
            'siteName' => (string) \get_bloginfo('name'),
            'serverName' => $this->server_name(),
            'restUrl' => \esc_url_raw(\rest_url('vormbyte-visual/v1/')),
            'workspaceUrl' => \esc_url_raw($this->get_workspace_url()),
            'mcpbUrl' => \esc_url_raw(\wp_nonce_url(
                \admin_url('admin-post.php?action=vormbyte_visual_download_mcpb'),
                'vormbyte_visual_download_mcpb',
            )),
            'authCheckUrl' => \esc_url_raw(\admin_url('admin-ajax.php?action=vormbyte_visual_auth_check')),
            'loginUrl' => \esc_url_raw(\wp_login_url($this->get_workspace_url())),
            'visualVersion' => (string) VORMBYTE_VISUAL_VERSION,
            'abilitiesEnabled' => $abilities_enabled,
            'looksProduction' => $looks_production,
            // Read mode state at page load; the workspace refuses page actions and editor tools that write.
            'readMode' => $read_mode,
            'readModeMessage' => $read_mode ? \vormbyte_read_mode_blocked_message('{subject}') : '',
            'nonce' => \wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Stream a downloadable .mcpb bundle for Claude Desktop. Hooked on admin_post.
     *
     * Unlike the core Vormbyte bundle, the Visual bundle carries no credentials:
     * it only points the npx MCP server at this site's workspace URL, so it is
     * safe to keep and share.
     */
    public function handle_download_mcpb(): void
    {
        if (!\vormbyte_current_user_can_manage()) {
            \wp_die(\esc_html__('You are not allowed to download this bundle.', domain: 'vormbyte'));
        }

        \check_admin_referer('vormbyte_visual_download_mcpb');

        if (!\class_exists('ZipArchive')) {
            \wp_die(\esc_html__(
                'Cannot build the bundle: the PHP zip extension is not available on this server. Use the JSON config instead.',
                domain: 'vormbyte',
            ));
        }

        $manifest = $this->build_mcpb_manifest($this->get_workspace_url(), $this->server_name());
        $manifest_json = (string) \wp_json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        $stub =
            "// Placeholder entry point. The actual MCP server is launched via mcp_config\n"
            . "// (npx vormbyte-visual-mcp), so this file is never executed.\n"
            . "// It exists only to satisfy the manifest's required entry_point field.\n";

        $tmp = \wp_tempnam('vormbyte-visual-mcpb');
        $zip = new \ZipArchive();
        if ($tmp === '' || $zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            \wp_die(\esc_html__('Could not create the bundle archive.', domain: 'vormbyte'));
        }
        $zip->addFromString('manifest.json', $manifest_json);
        $zip->addFromString('server/index.js', $stub);
        $zip->close();

        $host = (string) \wp_parse_url(\home_url(), PHP_URL_HOST);
        $filename = 'vormbyte-visual-' . \sanitize_file_name($host !== '' ? $host : 'site') . '.mcpb';

        \nocache_headers();
        \header('Content-Type: application/octet-stream');
        \header('Content-Disposition: attachment; filename="' . $filename . '"');
        \header('Content-Length: ' . (string) \filesize($tmp));
        \readfile($tmp);
        \wp_delete_file($tmp);
        exit();
    }

    /**
     * Build the MCPB manifest (manifest.json contents) for this site's workspace.
     * The server is launched with npx and pointed at the workspace URL; no
     * credentials are embedded.
     *
     * @return array<string, mixed>
     */
    private function build_mcpb_manifest(string $workspace_url, string $server_name): array
    {
        $site_name = \vormbyte_unbranded_site_name((string) \get_bloginfo('name'));
        $display_name = $site_name !== '' ? 'Vormbyte Visual: ' . $site_name : 'Vormbyte Visual';

        return [
            'manifest_version' => '0.3',
            'name' => $server_name,
            'display_name' => $display_name,
            'version' => (string) VORMBYTE_VISUAL_VERSION,
            'description' => \__(
                'Watch your AI agent work on this WordPress site, live in a browser workspace.',
                domain: 'vormbyte',
            ),
            'author' => ['name' => 'Vormbyte'],
            'server' => [
                // entry_point is required by the MCPB schema even though the server
                // is launched via mcp_config (npx); the bundled stub is never run.
                'type' => 'node',
                'entry_point' => 'server/index.js',
                'mcp_config' => [
                    'command' => 'npx',
                    'args' => ['-y', 'vormbyte-visual-mcp@latest'],
                    'env' => ['VORMBYTE_VISUAL_WORKSPACE_URL' => $workspace_url],
                ],
            ],
        ];
    }

    /**
     * Unique MCP server name for this site, capped at 25 characters. The same value
     * names the .mcpb bundle and is passed to the workspace for its JSON configs.
     *
     * The shared helpers live in includes/connect-page.php, which vormbyte.php loads
     * unconditionally before any feature (this one included) boots on plugins_loaded.
     */
    private function server_name(): string
    {
        $site = \vormbyte_get_mcp_server_site();

        // Visual has always lowercased the host before stripping "www.".
        return \vormbyte_build_mcp_server_name_default(
            \strtolower($site['host']),
            $site['port'],
            $site['path'],
            prefix: 'vormbyte-visual-',
        );
    }

    private function get_workspace_url(): string
    {
        return \admin_url('admin-post.php?action=vormbyte-visual');
    }
}
