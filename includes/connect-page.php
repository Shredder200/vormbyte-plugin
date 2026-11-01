<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Dashboard connect page — creates application passwords and shows MCP config samples.
 */

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/connect-methods.php';

/**
 * Enable AI Abilities for the current site domain.
 */
function vormbyte_enable_ai_abilities(): bool
{
    if (function_exists('vormbyte_get_mcp_dependency_error') && vormbyte_get_mcp_dependency_error() !== null) {
        return false;
    }

    update_option(option: 'vormbyte_ai_abilities_enabled', value: '1');
    update_option(option: 'vormbyte_ai_abilities_domain', value: vormbyte_ai_abilities_site_host());
    return true;
}

/**
 * Disable AI Abilities and clear the domain lock.
 */
function vormbyte_disable_ai_abilities(): bool
{
    update_option(option: 'vormbyte_ai_abilities_enabled', value: '0');
    delete_option('vormbyte_ai_abilities_domain');
    return true;
}

/**
 * Handle the enable/disable AI Abilities toggle submission.
 * Returns true on save, null when no submission.
 */
function vormbyte_handle_toggle_enabled(): ?bool
{
    if (($_POST['vormbyte_submit'] ?? null) === null) {
        return null;
    }
    if (!vormbyte_current_user_can_manage()) {
        return null;
    }

    check_admin_referer('vormbyte_settings');

    $enabled = ($_POST['vormbyte_ai_abilities_enabled'] ?? null) !== null;
    return $enabled ? vormbyte_enable_ai_abilities() : vormbyte_disable_ai_abilities();
}

/**
 * Handle the admin-bar AI Abilities toggle.
 */
function vormbyte_handle_admin_bar_toggle(): void
{
    if (!vormbyte_current_user_can_manage()) {
        wp_die(esc_html__('You are not allowed to manage Vormbyte settings.', domain: 'vormbyte'));
    }

    check_admin_referer('vormbyte_toggle_ai_abilities');

    $target = $_GET['vormbyte_target'] ?? '';
    $result = null;
    if ($target === 'on') {
        $result = vormbyte_enable_ai_abilities();
    }
    if ($target === 'off') {
        $result = vormbyte_disable_ai_abilities();
    }

    $redirect = wp_get_referer();
    if (!is_string($redirect) || $redirect === '') {
        $redirect = admin_url('admin.php?page=vormbyte-connect');
    }

    $redirect = add_query_arg([
        'vormbyte_toggle_result' => $result === true ? $target : 'failed',
    ], $redirect);

    wp_safe_redirect($redirect);
    exit();
}

function vormbyte_render_enable_toggle(): void
{
    $enabled = vormbyte_is_enabled();
    $dependency_error = function_exists('vormbyte_get_mcp_dependency_error')
        ? vormbyte_get_mcp_dependency_error()
        : null;
    $toggle_disabled = $dependency_error !== null && !$enabled;
    $submit_attributes = $toggle_disabled ? ['disabled' => 'disabled'] : [];
    $looks_production = vormbyte_looks_like_production();
    ?>
    <h2 class="vormbyte-step-heading">
        <span class="vormbyte-step-badge">1</span>
        <?php esc_html_e('Enable AI Abilities', domain: 'vormbyte'); ?>
    </h2>
    <form method="post" action="" id="vormbyte-settings-form" style="margin: 16px 0 0;">
        <?php wp_nonce_field('vormbyte_settings'); ?>
        <input type="hidden" name="vormbyte_submit" value="1" />
        <label style="display:flex; align-items:center; gap:10px; font-size:16px; font-weight:600; color:#1d2327; margin:0 0 12px;">
            <input type="checkbox" name="vormbyte_ai_abilities_enabled" value="1" id="vormbyte-enable-checkbox" style="width:18px; height:18px;" <?php checked(
                checked: $enabled,
                current: true,
            ); ?> <?php disabled($toggle_disabled); ?> />
            <span><?php esc_html_e('Turn on AI Abilities for this site', domain: 'vormbyte'); ?></span>
        </label>
        <p class="description" style="margin:0 0 8px;">
            <strong style="color:#d63638;"><?php esc_html_e('Security note:', domain: 'vormbyte'); ?></strong>
            <?php esc_html_e(
                'When enabled, AI agents can execute PHP code and perform filesystem operations on this site. For development and staging environments only. Always keep backups.',
                domain: 'vormbyte',
            ); ?>
        </p>
        <p class="description" style="margin:0 0 14px;">
            <?php esc_html_e(
                'Use Vormbyte with a capable AI model and set your AI tool to ask for confirmation before every action. Read what the agent is about to do before approving.',
                domain: 'vormbyte',
            ); ?>
        </p>
        <?php submit_button(
            text: __('Save Settings', domain: 'vormbyte'),
            type: 'primary',
            name: 'vormbyte_submit',
            wrap: false,
            other_attributes: $submit_attributes,
        ); ?>
    </form>
    <script>
    document.getElementById('vormbyte-settings-form').addEventListener('submit', function (e) {
        var cb = document.getElementById('vormbyte-enable-checkbox');
        if (cb.checked && !cb.defaultChecked) {
            var msg = <?php echo
                wp_json_encode(
                    $looks_production
                        ? __(
                            'This looks like a production site. The plugin can stay installed here, but AI Abilities are not meant for live sites: enable them only on a staging or development copy. Continue anyway?',
                            domain: 'vormbyte',
                        )
                        : __(
                            'AI agents will be able to execute PHP code and access the filesystem. For development and staging environments only. Continue?',
                            domain: 'vormbyte',
                        ),
                )
            ; ?>;
            if (!confirm(msg)) {
                e.preventDefault();
            }
        }
    });
    </script>
    <?php
}

/**
 * Render the production-site warning banner above the enable toggle.
 *
 * Shown only when: AI Abilities are currently enabled AND the site looks like production
 * AND the current user has not dismissed the warning.
 */
function vormbyte_render_production_warning(): void
{
    if (!vormbyte_is_enabled()) {
        return;
    }
    if (!vormbyte_looks_like_production()) {
        return;
    }
    if (vormbyte_production_warning_dismissed()) {
        return;
    }
    ?>
    <div class="vormbyte-production-warning" role="alert">
        <p>
            <strong><?php esc_html_e('⚠️ This looks like a production site.', domain: 'vormbyte'); ?></strong>
            <?php esc_html_e(
                'Keeping the plugin installed here is fine, but AI Abilities should only be active on a staging or development copy. Make your changes there, then deploy the result the regular way. On production, keep AI Abilities off.',
                domain: 'vormbyte',
            ); ?>
        </p>
        <form method="post" style="margin:0;">
            <?php wp_nonce_field('vormbyte_dismiss_production_warning'); ?>
            <input type="hidden" name="vormbyte_dismiss_production_warning" value="1" />
            <button type="submit" name="vormbyte_dismiss_production_warning" class="button button-small">
                <?php esc_html_e('Dismiss', domain: 'vormbyte'); ?>
            </button>
        </form>
    </div>
    <?php
}

/**
 * Build a default MCP server name from the parsed parts of a site's stored home URL.
 *
 * Root sites retain the original host-only algorithm byte-for-byte. Subdirectory sites
 * include the path, with a short URL hash when the combined slug exceeds the 25-character cap.
 * The prefix counts toward the cap: the slug gets whatever the prefix leaves of 25 characters.
 */
function vormbyte_build_mcp_server_name_default(
    string $site_host,
    ?int $site_port,
    string $site_path,
    string $prefix = 'vormbyte-',
): string {
    $slug_budget = max(0, 25 - strlen($prefix));
    $site_slug = vormbyte_mcp_server_name_slug((string) preg_replace(
        pattern: '/^www\./',
        replacement: '',
        subject: $site_host,
    ));
    $site_slug = ltrim((string) preg_replace('/^vormbyte/', replacement: '', subject: $site_slug), characters: '-');

    $site_path = trim($site_path, characters: '/');
    if ($site_path === '') {
        $site_slug = substr($site_slug, offset: 0, length: $slug_budget);
        $site_slug = rtrim($site_slug, characters: '-');
        return $site_slug !== '' ? $prefix . $site_slug : rtrim($prefix, characters: '-');
    }

    $path_slug = vormbyte_mcp_server_name_slug($site_path);
    $site_slug = trim($site_slug . '-' . $path_slug, characters: '-');
    $site_slug = (string) preg_replace(pattern: '/-{2,}/', replacement: '-', subject: $site_slug);
    if ($path_slug !== '' && strlen($site_slug) <= $slug_budget) {
        return $prefix . $site_slug;
    }

    // Seven characters of the budget go to "-" plus the 6-character hash.
    $hash = vormbyte_mcp_server_name_hash($site_host, $site_port, $site_path);
    $site_slug = rtrim(substr($site_slug, offset: 0, length: max(0, $slug_budget - 7)), characters: '-');
    return $prefix . ($site_slug !== '' ? $site_slug . '-' : '') . $hash;
}

/**
 * Lowercase a URL component, replace every run of characters outside [a-z0-9-] with "-", and trim hyphens.
 */
function vormbyte_mcp_server_name_slug(string $value): string
{
    $slug = (string) preg_replace(pattern: '/[^a-z0-9-]+/', replacement: '-', subject: strtolower($value));
    return trim($slug, characters: '-');
}

/**
 * Return a short hash of the scheme-less, www-insensitive site identity.
 */
function vormbyte_mcp_server_name_hash(string $site_host, ?int $site_port, string $site_path): string
{
    $site_host = (string) preg_replace(pattern: '/^www\./', replacement: '', subject: strtolower($site_host));
    if ($site_port === 80 || $site_port === 443) {
        $site_port = null;
    }
    $identity = $site_host . ($site_port !== null ? ':' . (string) $site_port : '') . '/' . strtolower($site_path);
    return substr(md5($identity), offset: 0, length: 6);
}

/**
 * Parse the URL components that identify a WordPress site for its default MCP server name.
 *
 * @return array{host: string, port: int|null, path: string}|null
 */
function vormbyte_parse_mcp_server_site_url(string $site_url): ?array
{
    $site_host = (string) wp_parse_url($site_url, PHP_URL_HOST);
    if ($site_host === '') {
        return null;
    }

    $site_port = (int) wp_parse_url($site_url, PHP_URL_PORT);
    $site_path = (string) wp_parse_url($site_url, PHP_URL_PATH);
    return [
        'host' => $site_host,
        'port' => $site_port > 0 ? $site_port : null,
        'path' => $site_path,
    ];
}

/**
 * Return the URL components that identify the current site in its default MCP server names.
 *
 * Reads the stored home option first, so per-request home_url filters cannot change the name.
 *
 * @return array{host: string, port: int|null, path: string}
 */
function vormbyte_get_mcp_server_site(): array
{
    $stored_home = trim((string) get_option('home'));
    $site = $stored_home !== '' ? vormbyte_parse_mcp_server_site_url($stored_home) : null;
    if ($site === null) {
        $site = vormbyte_parse_mcp_server_site_url(home_url());
    }

    return $site ?? ['host' => 'wordpress', 'port' => null, 'path' => ''];
}

/**
 * Compute the default MCP server name for the current site.
 *
 * Capped at 25 characters total because some MCP clients reject longer server names.
 * Used as the placeholder default when no name has been saved by the user.
 */
function vormbyte_get_mcp_server_name_default(): string
{
    $site = vormbyte_get_mcp_server_site();

    return vormbyte_build_mcp_server_name_default($site['host'], $site['port'], $site['path']);
}

/**
 * Handle the "use existing password" form submission.
 *
 * Returns the pasted plaintext value (only for the current request — never persisted),
 * a WP_Error on validation failure, or null when no submission.
 *
 * @return string|WP_Error|null
 */
function vormbyte_handle_use_existing_password()
{
    if (($_POST['vormbyte_use_existing_password'] ?? null) === null) {
        return null;
    }

    if (!vormbyte_current_user_can_manage()) {
        return new WP_Error('forbidden', __(
            'You do not have permission to use application passwords.',
            domain: 'vormbyte',
        ));
    }

    check_admin_referer('vormbyte_use_existing_password');

    $raw = $_POST['vormbyte_existing_password'] ?? '';
    $value = is_string($raw) ? trim($raw) : '';
    if ($value === '') {
        return new WP_Error('empty', __('Paste the application password value before submitting.', domain: 'vormbyte'));
    }
    if (strlen($value) < 16) {
        return new WP_Error('too_short', __(
            'That does not look like an application password. WordPress application passwords are at least 16 characters long.',
            domain: 'vormbyte',
        ));
    }
    return $value;
}

/**
 * Handle the create-password form submission.
 * Returns the plaintext password on success, a WP_Error on failure, or null when no submission.
 *
 * @return string|WP_Error|null
 */
function vormbyte_handle_create_password()
{
    if (($_POST['vormbyte_create_password'] ?? null) === null) {
        return null;
    }

    if (!vormbyte_current_user_can_manage()) {
        return new WP_Error('forbidden', __(
            'You do not have permission to create application passwords.',
            domain: 'vormbyte',
        ));
    }

    check_admin_referer('vormbyte_create_password');

    $status = vormbyte_app_passwords_status();
    if (!$status['available']) {
        return new WP_Error('not_available', $status['message']);
    }

    $user_id = get_current_user_id();
    $raw_name = $_POST['vormbyte_password_name'] ?? '';
    $input_name = is_string($raw_name) ? trim($raw_name) : '';
    $app_name = $input_name !== '' ? 'Vormbyte: ' . $input_name : 'Vormbyte';

    // Avoid duplicate names — append a counter if one already exists.
    $existing = WP_Application_Passwords::get_user_application_passwords($user_id);
    $names = array_column($existing, 'name');
    if (in_array(needle: $app_name, haystack: $names, strict: true)) {
        $i = 2;
        while (in_array(needle: $app_name . ' ' . $i, haystack: $names, strict: true)) {
            $i++;
        }
        $app_name = $app_name . ' ' . $i;
    }

    $result = WP_Application_Passwords::create_new_application_password($user_id, ['name' => $app_name]);

    if (is_wp_error($result)) {
        return $result;
    }

    // $result[0] is the plaintext password.
    return $result[0];
}

/**
 * Handle the revoke-password form submission. Redirects on success.
 * Called before admin HTML from admin_init or the Connections page load hook.
 */
function vormbyte_handle_revoke_password(string $redirect_page = 'vormbyte-connect'): void
{
    if (($_POST['vormbyte_revoke_password'] ?? null) === null) {
        return;
    }

    if (!vormbyte_current_user_can_manage()) {
        return;
    }

    $uuid = $_POST['vormbyte_revoke_uuid'] ?? '';
    if (!is_string($uuid) || $uuid === '') {
        return;
    }

    check_admin_referer('vormbyte_revoke_password_' . $uuid);

    $user_id = get_current_user_id();
    WP_Application_Passwords::delete_application_password($user_id, $uuid);

    wp_safe_redirect(admin_url('admin.php?page=' . $redirect_page . '&vormbyte_result=revoked'));
    exit();
}

/**
 * Return the current user's Vormbyte Application Passwords.
 *
 * @return array<int, array<string, mixed>>
 */
function vormbyte_get_mcp_passwords(): array
{
    $user_id = get_current_user_id();
    $all = WP_Application_Passwords::get_user_application_passwords($user_id);
    return array_values(array_filter(array: $all, callback: 'vormbyte_is_application_password'));
}

/**
 * Render a single password row for the passwords table.
 *
 * @param array<string, mixed> $pw        Password item from WP_Application_Passwords.
 * @param string               $dt_format Date/time format string.
 */
function vormbyte_render_password_row(array $pw, string $dt_format): void
{
    $uuid = (string) ($pw['uuid'] ?? '');
    $name = (string) ($pw['name'] ?? '');
    $created_date = ($pw['created'] ?? null) !== null ? wp_date($dt_format, (int) $pw['created']) : false;
    $created = $created_date !== false ? $created_date : __('Unknown', domain: 'vormbyte');
    $last_used_date = ($pw['last_used'] ?? null) !== null ? wp_date($dt_format, (int) $pw['last_used']) : false;
    $last_used = $last_used_date !== false ? $last_used_date : __('Never', domain: 'vormbyte');
    $revoke_nonce = (string) wp_create_nonce('vormbyte_revoke_password_' . $uuid);
    ?>
    <tr>
        <td><strong><?php echo esc_html($name); ?></strong></td>
        <td><?php echo esc_html($created); ?></td>
        <td><?php echo esc_html($last_used); ?></td>
        <td>
            <form method="post" style="margin:0;" onsubmit="return confirm('<?php echo
                esc_js(__('Revoke this password? Any clients using it will lose access.', domain: 'vormbyte'))
            ; ?>');">
                <input type="hidden" name="vormbyte_revoke_uuid" value="<?php echo esc_attr($uuid); ?>" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($revoke_nonce); ?>" />
                <input type="hidden" name="vormbyte_revoke_password" value="1" />
                <button type="submit" name="vormbyte_revoke_password" class="button button-small vormbyte-revoke-btn"><?php esc_html_e(
                    'Revoke',
                    domain: 'vormbyte',
                ); ?></button>
            </form>
        </td>
    </tr>
    <?php
}

/**
 * MCP clients with a verified configuration format.
 *
 * @return array<string, string>
 */
function vormbyte_mcp_clients(): array
{
    return [
        'claude-code' => 'Claude Code',
        'claude-desktop' => 'Claude Desktop',
        'claude-ai' => 'Claude.ai',
        'chatgpt' => 'ChatGPT.com',
        'codex-app' => 'Codex in ChatGPT Desktop',
        'grok' => 'Grok.com',
        'grok-bot' => 'Grok Bot',
        'codex-cli' => 'Codex CLI',
        'antigravity' => 'Antigravity',
        'antigravity-cli' => 'Antigravity CLI',
        'cursor' => 'Cursor',
        'vscode' => 'VS Code',
        'github-copilot' => 'GitHub Copilot',
        'windsurf' => 'Windsurf',
        'cline' => 'Cline',
        'roo-code' => 'Roo Code',
        'amazon-q' => 'Amazon Q',
        'zed' => 'Zed',
        'kilo-code' => 'Kilo Code',
        'opencode' => 'OpenCode',
    ];
}

/**
 * Every product shown in the Configuration chooser. CLI-only agents join the
 * verified MCP products here, while aliases such as Codex CLI and Roo Code keep
 * one visible entry even though their installer and MCP identifiers differ.
 *
 * @return array<string, string>
 */
function vormbyte_ai_clients(): array
{
    $clients = vormbyte_mcp_clients();
    $cli_only_clients = [];
    foreach (vormbyte_cli_agents() as $agent => $details) {
        $client = match ($agent) {
            'codex' => 'codex-cli',
            'kilo' => 'kilo-code',
            'roo' => 'roo-code',
            default => $agent,
        };
        if (!array_key_exists($client, $clients)) {
            $cli_only_clients[$client] = $details['label'];
        }
    }
    uasort($cli_only_clients, static fn(string $left, string $right): int => strnatcasecmp($left, $right));
    return $clients + $cli_only_clients;
}

/**
 * Render the AI client chooser before the authentication-method step.
 */
// Inherent: each rendered tool carries its server-resolved OAuth, password, CLI, scope,
// and visibility capabilities so the browser can keep all four support steps stable.
// @mago-expect lint:cyclomatic-complexity
function vormbyte_render_ai_client_chooser(): void
{
    $name_placeholder = '__VORMBYTE_MCP_NAME__';
    $oauth_configs = vormbyte_build_oauth_configs(rest_url('mcp/vormbyte-oauth'), $name_placeholder);
    $password_configs = vormbyte_build_configs(
        rest_url: rest_url('mcp/vormbyte'),
        username: wp_get_current_user()->user_login,
        // This is the visible documentation placeholder, never a credential.
        // @mago-expect lint:no-literal-password
        display_password: 'YOUR-APP-PASSWORD',
        mcp_name: $name_placeholder,
    );
    $clients = vormbyte_ai_clients();
    $cli_transport_available = vormbyte_cli_transport_allowed(home_url('/'), wp_get_environment_type());
    // Preserve the concise chooser users already know; CLI-only agents remain
    // available through search and the explicit expanded view.
    $common_clients = array_fill_keys(keys: array_keys(vormbyte_mcp_clients()), value: true);
    ?>
    <h2 class="vormbyte-step-heading">
        <span class="vormbyte-step-badge">2</span>
        <?php esc_html_e('Choose your AI tool', domain: 'vormbyte'); ?>
    </h2>
    <p class="description" style="margin:0;">
        <?php esc_html_e('Choose the app, CLI, or agent you use.', domain: 'vormbyte'); ?>
    </p>

    <label class="screen-reader-text" for="vormbyte-client-search"><?php esc_html_e(
        'Search AI tools',
        domain: 'vormbyte',
    ); ?></label>
    <input
        type="search"
        id="vormbyte-client-search"
        class="regular-text"
        placeholder="<?php esc_attr_e('Search your AI tool…', domain: 'vormbyte'); ?>"
        style="margin-top:12px; width:100%; max-width:420px;"
    >

    <div class="vormbyte-client-chooser-heading">
        <strong id="vormbyte-client-list-label"><?php esc_html_e('Common AI tools', domain: 'vormbyte'); ?></strong>
        <button
            type="button"
            class="button-link"
            id="vormbyte-client-show-all"
            aria-expanded="false"
            aria-controls="vormbyte-client-list"
        ><?php esc_html_e('Show all supported tools', domain: 'vormbyte'); ?></button>
    </div>

    <div
        class="vormbyte-client-tabs vormbyte-client-chooser"
        id="vormbyte-client-list"
        aria-labelledby="vormbyte-client-list-label"
        style="gap:8px;"
    >
        <?php foreach ($clients as $key => $label): ?>
            <?php

            $oauth_config = $oauth_configs[$key] ?? null;
            $oauth_supported = is_array($oauth_config) && ($oauth_config['kind'] ?? '') !== 'notice';
            $oauth_reason = is_array($oauth_config) && is_string($oauth_config['message'] ?? null)
                ? $oauth_config['message']
                : '';
            $cli_agent = vormbyte_cli_agent_for_client($key);
            $cli_details = $cli_agent !== null ? vormbyte_cli_agents()[$cli_agent] : null;
            // The companion CLI was published only under the original brand; no Vormbyte CLI package
            // exists, so the method stays hidden instead of telling users to install an unowned package.
            $cli_supported = false;
            $is_common = array_key_exists($key, $common_clients);
            ?>
            <button
                type="button"
                class="vormbyte-client-tab vormbyte-top-client-tab vormbyte-ai-client-choice"
                data-client="<?php echo esc_attr($key); ?>"
                data-oauth-supported="<?php echo $oauth_supported ? 'true' : 'false'; ?>"
                data-oauth-reason="<?php echo esc_attr($oauth_reason); ?>"
                data-password-supported="<?php echo array_key_exists($key, $password_configs) ? 'true' : 'false'; ?>"
                data-cli-supported="<?php echo $cli_supported ? 'true' : 'false'; ?>"
                data-cli-agent="<?php echo esc_attr($cli_agent ?? ''); ?>"
                data-cli-scope="<?php echo esc_attr($cli_details['scope'] ?? ''); ?>"
                data-common="<?php echo $is_common ? 'true' : 'false'; ?>"
                aria-pressed="false"
                <?php echo $is_common ? '' : 'hidden'; ?>
                onclick="vormbyteChooseAiClient('<?php echo esc_js($key); ?>', this)"
            ><?php echo esc_html($label); ?></button>
        <?php endforeach; ?>
    </div>
    <p id="vormbyte-client-no-results" class="description" hidden><?php esc_html_e(
        'No matching AI tool found.',
        domain: 'vormbyte',
    ); ?></p>

    <script>
    (function () {
        var storageKey = 'vormbyteSelectedAiClient';
        var oauthUnsupported = <?php echo
            wp_json_encode(__('%s does not support OAuth authentication with Vormbyte.', domain: 'vormbyte'))
        ; ?>;
        var passwordUnsupported = <?php echo
            wp_json_encode(__(
                '%s requires OAuth, so Application Password authentication is not available.',
                domain: 'vormbyte',
            ))
        ; ?>;
        var noMethodsAvailable = <?php echo
            wp_json_encode(__(
                'No working connection method is available for %s on this site. Check that the site uses HTTPS and can be reached from the environment where the selected AI tool runs.',
                domain: 'vormbyte',
            ))
        ; ?>;
        var commonClientsLabel = <?php echo wp_json_encode(__('Common AI tools', domain: 'vormbyte')); ?>;
        var allClientsLabel = <?php echo wp_json_encode(__('All supported AI tools', domain: 'vormbyte')); ?>;
        var searchResultsLabel = <?php echo wp_json_encode(__('Search results', domain: 'vormbyte')); ?>;
        var showAllLabel = <?php echo wp_json_encode(__('Show all supported tools', domain: 'vormbyte')); ?>;
        var showFewerLabel = <?php echo wp_json_encode(__('Show fewer tools', domain: 'vormbyte')); ?>;
        var showingAllClients = false;
        window.vormbyteSelectedAiClient = '';

        function updateClientList() {
            var search = document.getElementById('vormbyte-client-search');
            var query = search.value.trim().toLowerCase();
            var selectedClient = window.vormbyteSelectedAiClient || '';
            var visibleCount = 0;
            document.querySelectorAll('.vormbyte-ai-client-choice').forEach(function (choice) {
                var matches = choice.textContent.toLowerCase().indexOf(query) !== -1;
                var common = choice.getAttribute('data-common') === 'true';
                var selected = choice.getAttribute('data-client') === selectedClient;
                var visible = query !== '' ? matches : (showingAllClients || common || selected);
                choice.hidden = !visible;
                if (visible) { visibleCount += 1; }
            });

            var list = document.getElementById('vormbyte-client-list');
            list.classList.toggle('is-filtering', query !== '');
            document.getElementById('vormbyte-client-list-label').textContent =
                query !== '' ? searchResultsLabel : (showingAllClients ? allClientsLabel : commonClientsLabel);
            var toggle = document.getElementById('vormbyte-client-show-all');
            toggle.hidden = query !== '';
            toggle.textContent = showingAllClients ? showFewerLabel : showAllLabel;
            toggle.setAttribute('aria-expanded', showingAllClients ? 'true' : 'false');
            document.getElementById('vormbyte-client-no-results').hidden = visibleCount !== 0;
        }

        function updateAuthenticationAvailability(choice) {
            var clientLabel = choice.textContent.trim();
            var availableMethods = 0;
            document.querySelectorAll('.vormbyte-method-card[data-method]').forEach(function (card) {
                var method = card.getAttribute('data-method');
                var transportAvailable = card.getAttribute('data-transport-available') !== 'false';
                var supported = choice.getAttribute('data-' + method + '-supported') === 'true';
                var unavailableReason = choice.getAttribute('data-' + method + '-reason') || '';
                var description = card.querySelector('.description');
                var recommendation = card.querySelector('.vormbyte-method-recommendation');
                if (description && !card.vormbyteDefaultDescription) {
                    card.vormbyteDefaultDescription = description.textContent;
                }

                card.disabled = !transportAvailable || !supported;
                card.hidden = card.disabled;
                card.setAttribute('aria-disabled', card.disabled ? 'true' : 'false');
                if (!card.disabled) { availableMethods += 1; }
                if (recommendation) {
                    recommendation.hidden = !supported || (method !== 'cli' && choice.getAttribute('data-cli-supported') === 'true');
                }

                if (description && !supported) {
                    var reason = unavailableReason || (method === 'password' ? passwordUnsupported : oauthUnsupported);
                    description.textContent = reason.replace('%s', clientLabel);
                    card.setAttribute('title', description.textContent);
                } else if (description && card.vormbyteDefaultDescription) {
                    description.textContent = card.vormbyteDefaultDescription;
                    card.removeAttribute('title');
                }

                if (card.disabled && card.classList.contains('is-active')) {
                    card.classList.remove('is-active');
                    document.querySelectorAll('.vormbyte-method-panel').forEach(function (panel) {
                        panel.hidden = true;
                    });
                    var step4Placeholder = document.getElementById('vormbyte-step4-placeholder');
                    if (step4Placeholder) { step4Placeholder.hidden = false; }
                    var hostingWarning = document.getElementById('vormbyte-hosting-oauth-warning');
                    if (hostingWarning) { hostingWarning.hidden = true; }
                }
            });

            var noMethodsNotice = document.getElementById('vormbyte-no-auth-methods');
            if (noMethodsNotice) {
                noMethodsNotice.hidden = availableMethods > 0;
                noMethodsNotice.querySelector('p').textContent = noMethodsAvailable.replace('%s', clientLabel);
            }
            var step3Placeholder = document.getElementById('vormbyte-step3-placeholder');
            if (step3Placeholder) { step3Placeholder.hidden = true; }

            var availableCards = Array.prototype.filter.call(
                document.querySelectorAll('.vormbyte-method-card[data-method]'),
                function (card) { return !card.disabled; }
            );
            var activeCard = document.querySelector('.vormbyte-method-card.is-active[data-method]');
            if (activeCard && activeCard.disabled) {
                activeCard.classList.remove('is-active');
                activeCard = null;
            }
            if (availableCards.length === 1 && window.vormbyteApplyAuthenticationMethod) {
                window.vormbyteApplyAuthenticationMethod(availableCards[0].getAttribute('data-method'));
            } else if (!activeCard && window.vormbyteClearAuthenticationMethod) {
                window.vormbyteClearAuthenticationMethod();
            }
        }

        window.vormbyteChooseAiClient = function (key, button, preserveMethod) {
            // A deliberate Step 2 choice starts a fresh route through Steps 3 and 4.
            // Only restoring the saved choice on page load may keep a submitted password flow.
            if (!preserveMethod && window.vormbyteClearAuthenticationMethod) {
                window.vormbyteClearAuthenticationMethod();
            }
            window.vormbyteSelectedAiClient = key;
            try { window.localStorage.setItem(storageKey, key); } catch (error) {}

            document.querySelectorAll('.vormbyte-ai-client-choice').forEach(function (choice) {
                var selected = choice === button;
                choice.classList.toggle('active', selected);
                choice.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            updateClientList();
            updateAuthenticationAvailability(button);

            var activeMethod = document.querySelector('.vormbyte-method-card.is-active[data-method]');
            if (activeMethod && window.vormbyteApplyAuthenticationMethod) {
                window.vormbyteApplyAuthenticationMethod(activeMethod.getAttribute('data-method'));
            }
        };

        document.getElementById('vormbyte-client-search').addEventListener('input', function () {
            updateClientList();
        });
        document.getElementById('vormbyte-client-show-all').addEventListener('click', function () {
            showingAllClients = !showingAllClients;
            updateClientList();
        });

        window.addEventListener('DOMContentLoaded', function () {
            var stored = '';
            try { stored = window.localStorage.getItem(storageKey) || ''; } catch (error) {}
            var choice = null;
            document.querySelectorAll('.vormbyte-ai-client-choice').forEach(function (candidate) {
                if (candidate.getAttribute('data-client') === stored) { choice = candidate; }
            });
            if (choice) {
                window.vormbyteChooseAiClient(stored, choice, true);
            }
            updateClientList();
        });
    }());
    </script>
    <?php
}

/**
 * Build the editable message offered when the detected hosting may filter AI client traffic.
 */
function vormbyte_hosting_support_email(string $authentication_context = 'both'): string
{
    $site_url = home_url('/');
    $protected_resource_url = home_url('/.well-known/oauth-protected-resource');
    $current_user_url = rest_url('wp/v2/users/me');
    $failure_description = match ($authentication_context) {
        'oauth' => __(
            'The affected AI client only supports OAuth, and its connection is currently failing before the request reaches WordPress.',
            domain: 'vormbyte',
        ),
        default => __(
            'Those clients authenticate in one of two ways, either with OAuth or with a WordPress Application Password, and both are currently failing before the request reaches WordPress.',
            domain: 'vormbyte',
        ),
    };

    return sprintf(
        /* translators: 1: site URL, 2: OAuth protected-resource URL, 3: current-user REST URL, 4: description of the failing authentication method */
        __(
            "Hello,\n\nMy WordPress site at %1\$s runs a plugin called Vormbyte, which lets AI clients such as Claude and ChatGPT connect to the site over the WordPress REST API. The connection is direct between the AI client and my site: the plugin is installed on my own WordPress, and nothing passes through any Vormbyte server. %4\$s I have verified the WordPress side already: the REST API answers, the endpoints are installed, and the site responds correctly to requests made from the server itself. The requests that fail are the ones arriving from outside, from datacenter addresses and with non-browser user agents, which is exactly what an AI client sends. Claude.ai, for example, can send these server-to-server requests from Anthropic infrastructure using a generic Python HTTP client signature such as python-httpx. A firewall can therefore classify them as automated bot traffic even though they are legitimate requests from the AI client.\n\nThere are three things I would ask you to check for my site.\n\nFirst, the Authorization header must reach PHP rather than being stripped by the web server or the proxy. WordPress Application Passwords are sent as HTTP Basic authentication and OAuth tokens as a Bearer header, so if that header is removed, every authenticated request is seen by WordPress as anonymous and the connection fails with a permission error. On Apache this usually means passing HTTP_AUTHORIZATION through, and on nginx or a proxy layer it means not dropping the header.\n\nSecond, these paths need to pass through to WordPress as dynamic requests, without page caching, without redirects to the homepage and without bot challenges: the whole REST API path /wp-json/ and its subpaths, plus /.well-known/oauth-authorization-server and /.well-known/oauth-protected-resource, including any subpath of those two. To be concrete about the traffic involved, the AI client connects to /wp-json/mcp/vormbyte-oauth when it authenticates with OAuth and to /wp-json/mcp/vormbyte when it uses an Application Password, and the OAuth sign-in itself also calls /wp-json/vormbyte/v1/oauth/. I am asking for the whole /wp-json/ path rather than only those, because the REST prefix changes on subdirectory installs and on sites using plain permalinks.\n\nThird, if a CDN, WAF or bot protection sits in front of the site, please allow this traffic by path rather than by user agent, since these clients do not present a browser signature and connect from datacenter IP ranges. Every other protection on the site can stay exactly as it is.\n\nIf it helps your investigation, two quick tests: a plain GET to %2\$s should return application/json with HTTP 200, and a GET to %3\$s sent with HTTP Basic credentials should return the user rather than a 401. Anything else, a redirect, an HTML page, a 403 or a challenge page, is the failure I am reporting.\n\nIf your team would like to reproduce the behaviour on your own infrastructure, the plugin ships a Troubleshoot page that runs these same checks and prints exactly which layer is answering, which may be quicker than working from my description alone.\n\nThank you for your help.",
            domain: 'vormbyte',
        ),
        $site_url,
        $protected_resource_url,
        $current_user_url,
        $failure_description,
    );
}

/**
 * Stable Step 3 chooser. It contains the three possible connection routes, while
 * the selected client hides routes that do not apply. If exactly one remains, JS
 * selects it automatically without removing or renumbering the step.
 */
// The branches render the two authentication cards and their hosting-specific recommendation copy.
// @mago-expect lint:cyclomatic-complexity
function vormbyte_render_method_chooser(
    ?string $new_password,
    ?string $existing_password = null,
    ?WP_Error $existing_error = null,
): void {
    // Security-first: recommend OAuth (no secret in the config, mcp scope, revocable) in
    // every case except a local site on self-signed HTTPS, where the browser cannot verify
    // the certificate during sign-in; there the password flow (no browser step) is smoother.
    // OAuth is only offered where its transport is safe (HTTPS, or a local site). On a public
    // HTTP site it is not selectable; WordPress already blocks application passwords there too.
    $oauth_available = vormbyte_oauth_transport_allowed();
    $password_available = vormbyte_app_passwords_status()['available'];
    $hosting = \Vormbyte\Hosting\Detector::current();
    $hosting_name = is_array($hosting) ? $hosting['name'] : '';
    $hosting_edge = is_array($hosting) ? $hosting['edge'] : '';
    $hosting_prefers_password = match ($hosting['recommendation'] ?? null) {
        'password' => true,
        default => false,
    };
    $oauth_recommended =
        $oauth_available
        && !$hosting_prefers_password
        && !(vormbyte_host_unreachable_from_cloud() && vormbyte_likely_self_signed_https());
    // App password carries the recommendation on a local self-signed site and on a detected hosting
    // edge known to filter machine OAuth traffic. On a public HTTP site nothing is recommended.
    $password_recommended = $password_available && $oauth_available && !$oauth_recommended;
    $password_active = vormbyte_password_method_preselected($new_password, $existing_password, $existing_error);
    $has_password = $new_password !== null || $existing_password !== null;
    $badge_label = match (true) {
        $oauth_recommended => esc_html__('Recommended for your setup', domain: 'vormbyte'),
        $hosting_prefers_password => sprintf(
            /* translators: %s: detected hosting provider name */
            esc_html__('Recommended on %s', domain: 'vormbyte'),
            esc_html($hosting_name),
        ),
        default => esc_html__('Recommended for your local setup', domain: 'vormbyte'),
    };
    $badge = '<span class="vormbyte-recommended-badge vormbyte-method-recommendation">' . $badge_label . '</span>';
    ?>
    <h2 class="vormbyte-step-heading">
        <span class="vormbyte-step-badge">3</span>
        <?php esc_html_e('Choose how to connect', domain: 'vormbyte'); ?>
    </h2>
    <p id="vormbyte-step3-placeholder" class="description" style="margin:0 0 16px;">
        <?php esc_html_e('Choose your AI tool in step 2 to see its connection methods.', domain: 'vormbyte'); ?>
    </p>

    <div class="vormbyte-method-cards">
        <button
            type="button"
            class="vormbyte-method-card"
            data-method="cli"
            data-transport-available="true"
            hidden
            disabled
            aria-disabled="true"
        >
            <span class="vormbyte-method-title">
                <?php esc_html_e('Vormbyte CLI', domain: 'vormbyte'); ?>
                <span class="vormbyte-recommended-badge vormbyte-method-recommendation"><?php esc_html_e(
                    'Recommended',
                    domain: 'vormbyte',
                ); ?></span>
            </span>
            <span class="description"><?php esc_html_e(
                'A command-line tool that sets up supported coding agents to connect to and work with this WordPress site.',
                domain: 'vormbyte',
            ); ?></span>
        </button>
        <?php if ($oauth_available): ?>
        <button
            type="button"
            class="vormbyte-method-card"
            data-method="oauth"
            data-transport-available="true"
            hidden
            disabled
            aria-disabled="true"
        >
            <span class="vormbyte-method-title">
                <?php esc_html_e('OAuth', domain: 'vormbyte'); ?>
                <?php echo $oauth_recommended ? $badge : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </span>
            <span class="description"><?php esc_html_e(
                'Sign in through the browser, no password to copy.',
                domain: 'vormbyte',
            ); ?></span>
        </button>
        <?php endif; ?>
        <?php if (!$oauth_available): ?>
        <button
            type="button"
            class="vormbyte-method-card"
            data-method="oauth"
            data-transport-available="false"
            disabled
            aria-disabled="true"
        >
            <span class="vormbyte-method-title">
                <?php esc_html_e('OAuth', domain: 'vormbyte'); ?>
                <span
                    class="vormbyte-recommended-badge"
                    style="color:#8a6d1a; background:#fcf3d7;"
                ><?php esc_html_e('Requires HTTPS', domain: 'vormbyte'); ?></span>
            </span>
            <span class="description"><?php esc_html_e(
                'OAuth sends tokens over the network, so it needs HTTPS. Enable HTTPS on your site to use it.',
                domain: 'vormbyte',
            ); ?></span>
        </button>
        <?php endif; ?>
        <button
            type="button"
            class="vormbyte-method-card<?php echo $password_active ? ' is-active' : ''; ?>"
            data-method="password"
            data-transport-available="<?php echo $password_available ? 'true' : 'false'; ?>"
            hidden
            disabled
            aria-disabled="true"
        >
            <span class="vormbyte-method-title">
                <?php esc_html_e('Application password', domain: 'vormbyte'); ?>
                <?php echo $password_recommended ? $badge : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </span>
            <span class="description"><?php esc_html_e(
                'Generate a password and paste it into the selected tool’s config.',
                domain: 'vormbyte',
            ); ?></span>
        </button>
    </div>

    <div id="vormbyte-no-auth-methods" class="notice notice-warning inline" hidden>
        <p></p>
    </div>

    <?php if ($hosting_prefers_password): ?>
        <div id="vormbyte-hosting-oauth-warning" class="notice notice-warning inline is-dismissible" hidden>
            <p><strong><?php printf(
                /* translators: %s: detected hosting provider name */
                esc_html__('Connections from cloud AI clients may be blocked on %s.', domain: 'vormbyte'),
                esc_html($hosting_name),
            ); ?></strong></p>
            <p><?php printf(
                /* translators: 1: detected hosting provider name, 2: detected edge/security layer name */
                esc_html__(
                    'Vormbyte is direct by design: no credentials, site content, or requests pass through Vormbyte servers. With OAuth, the AI client opens the connection directly to this site. On Claude.ai, for example, the request originates on Anthropic servers and often uses a generic Python HTTP signature such as python-httpx. Vormbyte detected %1$s, which places %2$s in front of WordPress; that security layer can classify this legitimate server-to-server request as bot traffic and block or challenge it before WordPress or PHP receives it.',
                    domain: 'vormbyte',
                ),
                esc_html($hosting_name),
                esc_html($hosting_edge),
            ); ?></p>
            <p><?php printf(
                /* translators: %s: detected hosting provider name */
                esc_html__(
                    'For clients that support it, Application Password uses the local @automattic/mcp-wordpress-remote bridge launched by npx. It contacts WordPress from the environment where the AI client runs instead of from the AI provider’s cloud infrastructure, avoiding this type of bot classification. The bridge is not a Vormbyte service, and the traffic still never passes through Vormbyte servers. Claude.ai only supports OAuth, so for Claude.ai the solution is to contact %s support and ask them to allow legitimate machine-to-machine requests from Anthropic to /.well-known/oauth-* and /wp-json/ without browser or bot challenges.',
                    domain: 'vormbyte',
                ),
                esc_html($hosting_name),
            ); ?></p>
            <details class="vormbyte-hosting-support-email">
                <summary><?php printf(
                    /* translators: %s: detected hosting provider name */
                    esc_html__('Email template for %s support', domain: 'vormbyte'),
                    esc_html($hosting_name),
                ); ?></summary>
                <p class="description"><?php esc_html_e(
                    'Review this editable template before sending it, especially the statement that both authentication methods are failing.',
                    domain: 'vormbyte',
                ); ?></p>
                <label for="vormbyte-hosting-support-subject"><strong><?php esc_html_e(
                    'Subject',
                    domain: 'vormbyte',
                ); ?></strong></label>
                <input
                    type="text"
                    id="vormbyte-hosting-support-subject"
                    class="large-text"
                    value="<?php echo
                        esc_attr__(
                            'Request to allow AI client connections over the WordPress REST API',
                            domain: 'vormbyte',
                        )
                    ; ?>"
                />
                <label for="vormbyte-hosting-support-message"><strong><?php esc_html_e(
                    'Message',
                    domain: 'vormbyte',
                ); ?></strong></label>
                <textarea
                    id="vormbyte-hosting-support-message"
                    class="large-text code"
                    rows="24"
                ><?php echo esc_textarea(vormbyte_hosting_support_email()); ?></textarea>
                <p>
                    <button
                        type="button"
                        class="button"
                        onclick="vormbyteCopyHostingSupportEmail(this)"
                    ><?php esc_html_e('Copy email', domain: 'vormbyte'); ?></button>
                </p>
            </details>
        </div>
    <?php endif; ?>

    <div class="vormbyte-method-panel" data-panel="password"<?php echo $password_active ? '' : ' hidden'; ?>>
        <?php vormbyte_render_password_step($new_password, $existing_password, $existing_error); ?>
    </div>

    <noscript>
        <style>.vormbyte-method-panel[hidden] { display: block; }</style>
    </noscript>

    <script>
    (function () {
        var hasPassword = <?php echo $has_password ? 'true' : 'false'; ?>;
        // Re-query on every click so panels rendered in the later connection section are toggled too.
        window.vormbyteApplyAuthenticationMethod = function (method) {
            var card = document.querySelector('.vormbyte-method-card[data-method="' + method + '"]');
            if (!card || card.disabled) { return; }
            document.querySelectorAll('.vormbyte-method-card').forEach(function (c) {
                c.classList.toggle('is-active', c.getAttribute('data-method') === method);
            });
            document.querySelectorAll('.vormbyte-method-panel').forEach(function (p) {
                p.hidden = p.getAttribute('data-panel') !== method;
            });
            var selectedClient = window.vormbyteSelectedAiClient || '';
            var ready = selectedClient !== '' && (method === 'cli' || method === 'oauth' || (method === 'password' && hasPassword));
            var placeholder = document.getElementById('vormbyte-step4-placeholder');
            if (placeholder) { placeholder.hidden = selectedClient !== ''; }
            var hostingWarning = document.getElementById('vormbyte-hosting-oauth-warning');
            if (hostingWarning) { hostingWarning.hidden = method !== 'oauth'; }

            if (ready && method === 'cli' && window.vormbyteCliSetClient) {
                window.vormbyteCliSetClient(selectedClient);
            }
            if (ready && method === 'oauth' && window.vormbyteOauthSetClient) {
                window.vormbyteOauthSetClient(selectedClient);
            }
            if (ready && method === 'password' && window.vormbyteSetClient) {
                window.vormbyteSetClient(selectedClient);
            }
        };
        window.vormbyteClearAuthenticationMethod = function () {
            document.querySelectorAll('.vormbyte-method-card').forEach(function (card) {
                card.classList.remove('is-active');
            });
            document.querySelectorAll('.vormbyte-method-panel').forEach(function (panel) {
                panel.hidden = true;
            });
            var placeholder = document.getElementById('vormbyte-step4-placeholder');
            if (placeholder) { placeholder.hidden = false; }
            var hostingWarning = document.getElementById('vormbyte-hosting-oauth-warning');
            if (hostingWarning) { hostingWarning.hidden = true; }
        };
        document.querySelectorAll('.vormbyte-method-card').forEach(function (card) {
            card.addEventListener('click', function () {
                window.vormbyteApplyAuthenticationMethod(card.getAttribute('data-method'));
            });
        });
    })();
    </script>
    <?php
}

/**
 * Whether the app-password method is pre-selected on load: true when a password action happened
 * this request (a new or existing password, or an error to surface). OAuth is never pre-selected;
 * the user picks it. Shared by the chooser and the step 4 section so both agree on the initial
 * panel visibility.
 */
function vormbyte_password_method_preselected(
    ?string $new_password,
    ?string $existing_password,
    ?WP_Error $existing_error,
): bool {
    return $new_password !== null || $existing_password !== null || $existing_error !== null;
}

/**
 * Notice for a local self-signed HTTPS site, explaining the NODE_TLS_REJECT_UNAUTHORIZED flag the
 * connection config carries. Shared by the app-password and OAuth flows so the wording is identical;
 * renders nothing unless the site looks self-signed.
 */
function vormbyte_render_local_https_notice(): void
{
    if (!vormbyte_likely_self_signed_https()) {
        return;
    }
    ?>
    <div class="notice notice-warning inline" style="margin:0 0 12px;">
        <p style="margin:0;">
            <strong><?php esc_html_e('Local HTTPS detected.', domain: 'vormbyte'); ?></strong>
            <?php printf(
                /* translators: %s: the NODE_TLS_REJECT_UNAUTHORIZED=0 flag, wrapped in <code> tags */
                esc_html__(
                    'Your certificate is not publicly trusted (normal for local development), so the config sets %s. This turns off TLS certificate verification for the whole npx process, including while it downloads the package, so only use it on a network you trust.',
                    domain: 'vormbyte',
                ),
                '<code>NODE_TLS_REJECT_UNAUTHORIZED=0</code>',
            ); ?>
        </p>
    </div>
    <?php
}

/**
 * Render the OAuth client-config section (Step 4). The selected client gets a native URL snippet,
 * a connector button, or the mcp-remote bridge, depending on the client and whether the site is
 * reachable from the cloud. Uses its own id namespace so it can coexist in the DOM with the
 * app-password config section (only one method panel is visible at a time).
 */
function vormbyte_render_oauth_config_section(string $rest_url): void
{
    if (!vormbyte_oauth_transport_allowed()) {
        return;
    }
    $default_name = vormbyte_get_mcp_server_name_default();
    $name_placeholder = '__VORMBYTE_MCP_NAME__';
    $configs = vormbyte_build_oauth_configs($rest_url, $name_placeholder);
    $configs_json = (string) wp_json_encode($configs);

    $clients = array_intersect_key(vormbyte_ai_clients(), $configs);
    ?>
    <div id="vormbyte-oauth-content" style="display:none; margin-top:16px;">
        <?php vormbyte_render_local_https_notice(); ?>

        <div id="vormbyte-oauth-connector-wrap" style="display:none; margin-bottom:12px;">
            <a
                id="vormbyte-oauth-connector-btn"
                class="button button-primary"
                style="padding:12px 24px; height:auto; font-size:15px;"
                href="#"
                target="_blank"
                rel="noopener"
            ><?php esc_html_e('Add the connector', domain: 'vormbyte'); ?></a>
        </div>

        <div id="vormbyte-oauth-deeplink-wrap" style="display:none; margin-bottom:12px;">
            <a
                id="vormbyte-oauth-deeplink-btn"
                class="button button-primary"
                style="padding:12px 24px; height:auto; font-size:15px;"
                href="#"
            ><?php esc_html_e('One-click install', domain: 'vormbyte'); ?></a>
        </div>

        <div
            id="vormbyte-oauth-notice"
            style="display:none; margin:4px 0 0; padding:12px 14px; background:#f0f6fc; border:1px solid #c3d9ed; border-radius:6px; font-size:14px;"
        ></div>

        <div id="vormbyte-oauth-name-wrap">
        <p style="margin:8px 0 4px;">
            <button
                type="button"
                class="button-link"
                id="vormbyte-oauth-name-toggle"
                aria-expanded="false"
                aria-controls="vormbyte-oauth-name-field"
                onclick="vormbyteOauthToggleName(this)"
            ><?php esc_html_e('Change server name (optional)', domain: 'vormbyte'); ?></button>
        </p>
        <div id="vormbyte-oauth-name-field" hidden style="display:none; margin:6px 0 14px;">
            <input
                type="text"
                id="vormbyte-oauth-name"
                value="<?php echo esc_attr($default_name); ?>"
                placeholder="<?php echo esc_attr($default_name); ?>"
                maxlength="25"
                style="width:220px;"
                oninput="vormbyteOauthUpdateName(this.value)"
            >
            <p class="description" style="margin:6px 0 0;">
                <?php esc_html_e(
                    'Give the server a name you’ll recognize. The steps and snippets below update as you type.',
                    domain: 'vormbyte',
                ); ?>
            </p>
            <div id="vormbyte-oauth-name-warning" class="notice notice-warning inline" style="display:none; margin:8px 0 0;">
                <p style="margin:0;">
                    <?php esc_html_e(
                        'Maximum 25 characters reached. Required for tool compatibility.',
                        domain: 'vormbyte',
                    ); ?>
                </p>
            </div>
            <div
                id="vormbyte-oauth-name-suggestion"
                class="notice notice-warning inline"
                style="display:none; margin:8px 0 0;"
            >
                <p style="margin:0;">
                    <?php esc_html_e(
                        'Tip: keep "vormbyte" in the name so you (and your AI agent) can tell this MCP server apart from others.',
                        domain: 'vormbyte',
                    ); ?>
                </p>
            </div>
        </div>
        </div>

        <div id="vormbyte-oauth-hint" style="font-size:13px; color:#666; padding:6px 0 0;"></div>

        <div id="vormbyte-oauth-manual-btn-wrap" style="display:none;">
            <hr style="border:none; border-top:1px solid #dcdcde; margin:12px 0 8px;">
            <button
                type="button"
                class="button button-secondary"
                id="vormbyte-oauth-manual-toggle"
                aria-expanded="false"
                aria-controls="vormbyte-oauth-manual"
                onclick="vormbyteOauthToggleManual(this)"
            ><?php esc_html_e('Manual configuration', domain: 'vormbyte'); ?></button>
        </div>

        <div id="vormbyte-oauth-manual" style="display:none; margin-top:14px;">
            <ol
                id="vormbyte-oauth-steps"
                style="display:none; list-style-type:lower-alpha; margin:0 0 4px; padding-left:22px;"
            ></ol>
        </div>
    </div>

    <script>
    (function () {
        var configs = <?php echo $configs_json; ?>;
        var client = '';
        var defaultName = <?php echo wp_json_encode($default_name); ?>;
        var mcpName = defaultName;
        var namePlaceholder = <?php echo wp_json_encode($name_placeholder); ?>;
        var clientLabels = <?php echo wp_json_encode($clients); ?>;
        var manualLabelPrefix = <?php echo wp_json_encode(__('Manual configuration for', domain: 'vormbyte')); ?>;
        var connectorLabelPrefix = <?php echo wp_json_encode(__('Add the connector to', domain: 'vormbyte')); ?>;
        var deeplinkLabelPrefix = <?php echo wp_json_encode(__('One-click install in', domain: 'vormbyte')); ?>;
        var stepOpenLabel = <?php echo wp_json_encode(__('Open your config', domain: 'vormbyte')); ?>;
        var stepAddLabel = <?php echo wp_json_encode(__('Add this server', domain: 'vormbyte')); ?>;
        var stepAddNote = <?php echo
            wp_json_encode(__(
                'If your config file already has content, merge this into your existing config instead of replacing it.',
                domain: 'vormbyte',
            ))
        ; ?>;
        var stepRunLabel = <?php echo wp_json_encode(__('Run this in your terminal', domain: 'vormbyte')); ?>;
        var copyLabel = <?php echo wp_json_encode(__('Copy', domain: 'vormbyte')); ?>;
        var copiedLabel = <?php echo wp_json_encode(__('Copied!', domain: 'vormbyte')); ?>;
        var stepSignInLabel = <?php echo wp_json_encode(__('Sign in', domain: 'vormbyte')); ?>;
        var stepSignInNote = <?php echo
            wp_json_encode(__(
                'The next time your AI tool connects, your browser opens so you can authorize it. Approve to finish connecting.',
                domain: 'vormbyte',
            ))
        ; ?>;
        var stepSignInRestartLabel = <?php echo wp_json_encode(__('Restart and sign in', domain: 'vormbyte')); ?>;
        var stepSignInRestartNote = <?php echo
            wp_json_encode(__(
                'Restart your AI tool so it loads the server. On the next start your browser opens to sign in and authorize. Approve to finish.',
                domain: 'vormbyte',
            ))
        ; ?>;
        var editConfigNote = <?php echo
            wp_json_encode(__(
                'In Claude Desktop, open Settings → Developer → Edit Config to open this file.',
                domain: 'vormbyte',
            ))
        ; ?>;

        var manualOpen = false;

        function setDisplay(id, show) {
            document.getElementById(id).style.display = show ? '' : 'none';
        }

        function esc(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function withName(s) {
            return esc(String(s).split(namePlaceholder).join(mcpName));
        }

        // Config-file clients: turn the snippet + its file locations into a two-step guide (open the
        // config at its real path, then paste the snippet). Connector clients ship explicit steps.
        function signInStep(needsRestart) {
            return needsRestart
                ? { title: stepSignInRestartLabel, body: stepSignInRestartNote }
                : { title: stepSignInLabel, body: stepSignInNote };
        }

        function buildConfigSteps(cfg) {
            if (cfg.isShell) {
                return [{ title: stepRunLabel, code: cfg.code }, signInStep(false)];
            }
            var steps = [];
            var keys = cfg.paths ? Object.keys(cfg.paths) : [];
            if (keys.length) {
                var bodyHtml = keys.map(function (label) {
                    return '<code>' + esc(cfg.paths[label]) + '</code> (' + esc(label) + ')';
                }).join('<br>');
                // Claude Desktop opens this exact file from its own UI, so point there first.
                if (client === 'claude-desktop') {
                    bodyHtml = esc(editConfigNote) + '<br>' + bodyHtml;
                }
                steps.push({ title: stepOpenLabel, bodyHtml: bodyHtml });
            }
            var addStep = { title: stepAddLabel, body: stepAddNote, code: cfg.code };
            if (cfg.note) { addStep.noteHtml = cfg.note; }
            steps.push(addStep);
            steps.push(signInStep(true));
            return steps;
        }

        function renderSteps(steps) {
            var html = '';
            steps.forEach(function (s) {
                html += '<li style="margin:0 0 12px;"><strong>' + esc(s.title) + '</strong>';
                if (s.bodyHtml) {
                    html += '<div style="margin-top:2px;">' + s.bodyHtml + '</div>';
                } else if (s.body) {
                    html += '<div style="margin-top:2px;">' + withName(s.body) + '</div>';
                }
                if (s.copy) {
                    html +=
                        '<div style="margin-top:6px;">' +
                        '<span style="display:inline-flex; align-items:center; gap:10px; max-width:100%; ' +
                        'background:#f6f7f7; border:1px solid #dcdcde; border-radius:6px; padding:5px 6px 5px 12px;">' +
                        '<span style="font-weight:600; word-break:break-all;">' +
                        withName(s.copy) +
                        '</span><button type="button" class="button button-small" style="flex:none;" ' +
                        'onclick="vormbyteOauthCopyChip(this)">' +
                        esc(copyLabel) +
                        '</button></span></div>';
                }
                if (s.code) {
                    html +=
                        '<div class="vormbyte-config-block" style="margin-top:6px;">' +
                        '<pre>' + withName(s.code) + '</pre>' +
                        '<button type="button" class="button vormbyte-copy-btn" onclick="vormbyteOauthCopyStep(this)">' +
                        esc(copyLabel) +
                        '</button></div>';
                }
                if (s.noteHtml) {
                    html +=
                        '<div style="margin-top:6px; color:#646970; font-size:13px;">' +
                        s.noteHtml.split(namePlaceholder).join(esc(mcpName)) +
                        '</div>';
                }
                html += '</li>';
            });
            document.getElementById('vormbyte-oauth-steps').innerHTML = html;
        }

        function render() {
            if (!client) { return; }
            var cfg = configs[client];
            if (!cfg) { return; }

            // A message-only client (e.g. a cloud client on a local site): show the explanation and
            // hide every interactive part, including the server-name field.
            var isNotice = cfg.kind === 'notice';
            setDisplay('vormbyte-oauth-notice', isNotice);
            setDisplay('vormbyte-oauth-name-wrap', !isNotice);
            if (isNotice) {
                document.getElementById('vormbyte-oauth-notice').textContent = cfg.message || '';
                setDisplay('vormbyte-oauth-connector-wrap', false);
                setDisplay('vormbyte-oauth-deeplink-wrap', false);
                setDisplay('vormbyte-oauth-hint', false);
                setDisplay('vormbyte-oauth-steps', false);
                setDisplay('vormbyte-oauth-manual-btn-wrap', false);
                setDisplay('vormbyte-oauth-manual', false);
                return;
            }

            var isConnector = cfg.kind === 'connector';
            var hasDeeplink = !!cfg.deeplink;
            var hasSteps = !!(cfg.steps && cfg.steps.length);
            var hasCode = !!cfg.code;
            var hasManual = hasSteps || hasCode;
            var hasPrimary = isConnector || hasDeeplink;

            var label = clientLabels[client] || '';
            setDisplay('vormbyte-oauth-connector-wrap', isConnector);
            if (isConnector) {
                var connBtn = document.getElementById('vormbyte-oauth-connector-btn');
                connBtn.setAttribute('href', cfg.connector);
                connBtn.textContent = connectorLabelPrefix + ' ' + label;
            }

            setDisplay('vormbyte-oauth-deeplink-wrap', hasDeeplink);
            if (hasDeeplink) {
                var dl = cfg.deeplink.split(namePlaceholder).join(encodeURIComponent(mcpName));
                var dlBtn = document.getElementById('vormbyte-oauth-deeplink-btn');
                dlBtn.setAttribute('href', dl);
                dlBtn.textContent = deeplinkLabelPrefix + ' ' + label;
            }

            // The hint describes the OAuth connection method, so it only belongs with the one-click
            // primaries (connector / deeplink). For manual config-file clients the steps cover it.
            var showHint = hasPrimary && cfg.hint;
            var hintEl = document.getElementById('vormbyte-oauth-hint');
            hintEl.innerHTML = showHint ? cfg.hint : '';
            hintEl.style.display = showHint ? '' : 'none';

            setDisplay('vormbyte-oauth-steps', hasManual);
            if (hasSteps) {
                renderSteps(cfg.steps);
            } else if (hasCode) {
                renderSteps(buildConfigSteps(cfg));
            }

            // The manual guide is a fallback behind a toggle when there is a one-click primary
            // (connector or deeplink); with no primary it is the only way in, so show it directly.
            setDisplay('vormbyte-oauth-manual-btn-wrap', hasManual && hasPrimary);
            setDisplay('vormbyte-oauth-manual', hasManual && (!hasPrimary || manualOpen));
            document.getElementById('vormbyte-oauth-manual-toggle')
                .setAttribute('aria-expanded', manualOpen ? 'true' : 'false');
        }

        window.vormbyteOauthSetClient = function (key) {
            if (!configs[key]) { return; }
            client = key;
            manualOpen = false;
            if (clientLabels[key]) {
                document.getElementById('vormbyte-oauth-manual-toggle').textContent =
                    manualLabelPrefix + ' ' + clientLabels[key];
            }
            document.getElementById('vormbyte-oauth-content').style.display = '';
            render();
        };

        window.vormbyteOauthToggleManual = function (btn) {
            manualOpen = !manualOpen;
            btn.setAttribute('aria-expanded', manualOpen ? 'true' : 'false');
            setDisplay('vormbyte-oauth-manual', manualOpen);
        };

        function updateOauthNameWarning(value) {
            document.getElementById('vormbyte-oauth-name-warning').style.display = value.length >= 25 ? 'block' : 'none';
            var trimmed = value.trim();
            var missing = trimmed.length > 0 && trimmed.toLowerCase().indexOf('vormbyte') === -1;
            document.getElementById('vormbyte-oauth-name-suggestion').style.display = missing ? 'block' : 'none';
        }

        window.vormbyteOauthUpdateName = function (value) {
            mcpName = value.trim() || defaultName;
            updateOauthNameWarning(value);
            render();
        };

        window.vormbyteOauthToggleName = function (btn) {
            var field = document.getElementById('vormbyte-oauth-name-field');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            field.style.display = expanded ? 'none' : 'block';
            field.hidden = expanded;
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        };

        window.vormbyteOauthCopyStep = function (btn) {
            var pre = btn.previousElementSibling;
            if (!pre) { return; }
            window.vormbyteClipboardCopy(pre.textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = copiedLabel;
                setTimeout(function () { btn.textContent = orig; }, 1500);
            });
        };

        window.vormbyteOauthCopyChip = function (btn) {
            var value = btn.previousElementSibling;
            if (!value) { return; }
            window.vormbyteClipboardCopy(value.textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = copiedLabel;
                setTimeout(function () { btn.textContent = orig; }, 1500);
            });
        };
    }());
    </script>
    <?php
}

function vormbyte_cli_authorization_prompt(
    string $login_unix,
    string $login_windows,
    string $device_login_unix,
    string $device_login_windows,
): string {
    if ($login_unix === $login_windows) {
        return sprintf(
            /* translators: 1: browser-flow login command, 2: device-flow login command */
            __(
                "Then authorize the site by running:\n\n%1\$s\n\nKeep the login process running while I approve access in the browser. If the browser flow fails, times out, does not open a browser, or the environment is headless, retry automatically with:\n\n%2\$s\n\nShow me the verification URL and short code, ask me to approve that code from any device where I am already signed in to WordPress, and keep polling until authorization completes.",
                domain: 'vormbyte',
            ),
            $login_unix,
            $device_login_unix,
        );
    }

    return sprintf(
        /* translators: 1: POSIX browser-flow login, 2: PowerShell browser-flow login, 3: POSIX device-flow login, 4: PowerShell device-flow login */
        __(
            "Then authorize the site with the command for the current shell. In a POSIX shell run:\n\n%1\$s\n\nIn PowerShell run:\n\n%2\$s\n\nKeep the login process running while I approve access in the browser. If the browser flow fails, times out, does not open a browser, or the environment is headless, retry automatically with the matching device-flow command. In a POSIX shell run:\n\n%3\$s\n\nIn PowerShell run:\n\n%4\$s\n\nShow me the verification URL and short code, ask me to approve that code from any device where I am already signed in to WordPress, and keep polling until authorization completes.",
            domain: 'vormbyte',
        ),
        $login_unix,
        $login_windows,
        $device_login_unix,
        $device_login_windows,
    );
}

/**
 * Render the Vormbyte CLI setup route for every agent that can load the bundled
 * skill. The selected product swaps the installer destination without exposing
 * a credential: authorization happens in the browser after installation.
 */
function vormbyte_render_cli_config_section(): void
{
    $site_url = home_url('/');
    $allow_insecure_http = vormbyte_cli_needs_insecure_http_override($site_url, wp_get_environment_type());
    $allow_self_signed_https = vormbyte_likely_self_signed_https();
    $login_environment = [];
    if ($allow_insecure_http) {
        $login_environment['VORMBYTE_ALLOW_INSECURE_HTTP'] = '1';
    }
    if ($allow_self_signed_https) {
        $login_environment['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
    }
    $login_unix = vormbyte_cli_login_command($site_url, $login_environment);
    $login_windows = vormbyte_cli_windows_login_command($site_url, $login_environment);
    $authorization_prompt = vormbyte_cli_authorization_prompt(
        $login_unix,
        $login_windows,
        vormbyte_cli_device_login_command($site_url, $login_environment),
        vormbyte_cli_windows_device_login_command($site_url, $login_environment),
    );
    $hosting = \Vormbyte\Hosting\Detector::current();
    $hosting_note = '';
    if (is_array($hosting)) {
        $hosting_note = match ($hosting['recommendation']) {
            'password' => sprintf(
                /* translators: 1: detected hosting provider name, 2: detected edge/security layer name */
                __(
                    'This site is hosted on %1$s behind %2$s. Vormbyte CLI uses OAuth directly and has no MCP bridge, so requests originate from the environment where this coding agent runs. If the diagnostics show that requests are blocked before reaching WordPress, explain that cloud-hosted agent traffic may be filtered by the hosting layer and direct me to the hosting support template on the Vormbyte Configuration page.',
                    domain: 'vormbyte',
                ),
                $hosting['name'],
                $hosting['edge'],
            ),
            default => '',
        };
    }
    $configs = [];

    foreach (vormbyte_ai_clients() as $client => $label) {
        $agent = vormbyte_cli_agent_for_client($client);
        if ($agent === null) {
            continue;
        }
        $details = vormbyte_cli_agents()[$agent];
        $unix_install = vormbyte_cli_unix_install_command($agent, $details['scope']);
        $windows_install = vormbyte_cli_windows_install_command($agent, $details['scope']);
        $project_note = $details['scope'] === 'project'
            ? __(
                'This agent loads skills from the current project only. Run the installation from that project’s root directory.',
                domain: 'vormbyte',
            )
            : '';
        $prompt_notes = implode(' ', array_filter([$project_note, $hosting_note]));
        $configs[$client] = [
            'label' => $label,
            'agent' => $agent,
            'scope' => $details['scope'],
            'unixInstall' => $unix_install,
            'windowsInstall' => $windows_install,
            'unixLogin' => $login_unix,
            'windowsLogin' => $login_windows,
            'projectNote' => $project_note,
            'prompt' => sprintf(
                /* translators: 1: client label, 2: site URL, 3: POSIX installer, 4: PowerShell installer, 5: authorization instructions, 6: optional project/hosting note */
                __(
                    "I want to connect %1\$s to this WordPress site with Vormbyte CLI: %2\$s\n\nInspect the current shell and run exactly one installer. In a POSIX shell run:\n\n%3\$s\n\nIn PowerShell run:\n\n%4\$s\n\n%5\$s\n\nOnce authorized, run vormbyte doctor --json yourself and verify the connection. Do not ask me to run installation, login, or diagnostic commands. %6\$s",
                    domain: 'vormbyte',
                ),
                $label,
                $site_url,
                $unix_install,
                $windows_install,
                $authorization_prompt,
                $prompt_notes,
            ),
        ];
    }
    ?>
    <div id="vormbyte-cli-content" style="display:none; margin-top:16px;">
        <div id="vormbyte-cli-project-note" class="notice notice-info inline" hidden>
            <p></p>
        </div>

        <?php if ($allow_insecure_http): ?>
            <div class="notice notice-warning inline" style="margin:0 0 12px;">
                <p><?php esc_html_e(
                    'This local site uses HTTP on a non-loopback hostname. The command opts in to insecure HTTP for this login only. Use it only on a trusted development network.',
                    domain: 'vormbyte',
                ); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($allow_self_signed_https): ?>
            <div class="notice notice-warning inline" style="margin:0 0 12px;">
                <p><?php printf(
                    /* translators: %s: NODE_TLS_REJECT_UNAUTHORIZED=0 environment flag */
                    esc_html__(
                        'This local HTTPS certificate is not publicly trusted, so the login command sets %s for that CLI process. This disables certificate verification temporarily; use it only for a local site you trust.',
                        domain: 'vormbyte',
                    ),
                    '<code>NODE_TLS_REJECT_UNAUTHORIZED=0</code>',
                ); ?></p>
            </div>
        <?php endif; ?>

        <div class="vormbyte-paste-block">
            <div class="vormbyte-paste-content is-expanded">
                <pre id="vormbyte-cli-prompt"></pre>
            </div>
            <div class="vormbyte-paste-actions">
                <button type="button" class="button button-primary" onclick="vormbyteCopyCliPrompt(this)"><?php esc_html_e(
                    'Copy prompt',
                    domain: 'vormbyte',
                ); ?></button>
            </div>
        </div>

        <details id="vormbyte-cli-manual" style="margin-top:14px;">
            <summary><?php esc_html_e('Manual terminal commands', domain: 'vormbyte'); ?></summary>
            <h3><?php esc_html_e('macOS or Linux', domain: 'vormbyte'); ?></h3>
            <p class="description"><?php esc_html_e(
                'Install Vormbyte CLI and the agent skill:',
                domain: 'vormbyte',
            ); ?></p>
            <div class="vormbyte-config-block">
                <pre id="vormbyte-cli-unix-install"></pre>
                <button type="button" class="button vormbyte-copy-btn" onclick="vormbyteCopyCliCode('vormbyte-cli-unix-install', this)"><?php esc_html_e(
                    'Copy',
                    domain: 'vormbyte',
                ); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Authorize this WordPress site:', domain: 'vormbyte'); ?></p>
            <div class="vormbyte-config-block">
                <pre id="vormbyte-cli-unix-login"></pre>
                <button type="button" class="button vormbyte-copy-btn" onclick="vormbyteCopyCliCode('vormbyte-cli-unix-login', this)"><?php esc_html_e(
                    'Copy',
                    domain: 'vormbyte',
                ); ?></button>
            </div>

            <h3><?php esc_html_e('Windows PowerShell', domain: 'vormbyte'); ?></h3>
            <p class="description"><?php esc_html_e(
                'Install Vormbyte CLI and the agent skill:',
                domain: 'vormbyte',
            ); ?></p>
            <div class="vormbyte-config-block">
                <pre id="vormbyte-cli-windows-install"></pre>
                <button type="button" class="button vormbyte-copy-btn" onclick="vormbyteCopyCliCode('vormbyte-cli-windows-install', this)"><?php esc_html_e(
                    'Copy',
                    domain: 'vormbyte',
                ); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Authorize this WordPress site:', domain: 'vormbyte'); ?></p>
            <div class="vormbyte-config-block">
                <pre id="vormbyte-cli-windows-login"></pre>
                <button type="button" class="button vormbyte-copy-btn" onclick="vormbyteCopyCliCode('vormbyte-cli-windows-login', this)"><?php esc_html_e(
                    'Copy',
                    domain: 'vormbyte',
                ); ?></button>
            </div>
        </details>
    </div>

    <script>
    (function () {
        var configs = <?php echo wp_json_encode($configs); ?>;
        var copiedLabel = <?php echo wp_json_encode(__('Copied!', domain: 'vormbyte')); ?>;

        window.vormbyteCliSetClient = function (client) {
            var config = configs[client];
            if (!config) { return; }
            document.getElementById('vormbyte-cli-content').style.display = '';
            document.getElementById('vormbyte-cli-prompt').textContent = config.prompt;
            document.getElementById('vormbyte-cli-unix-install').textContent = config.unixInstall;
            document.getElementById('vormbyte-cli-unix-login').textContent = config.unixLogin;
            document.getElementById('vormbyte-cli-windows-install').textContent = config.windowsInstall;
            document.getElementById('vormbyte-cli-windows-login').textContent = config.windowsLogin;
            var projectNote = document.getElementById('vormbyte-cli-project-note');
            projectNote.hidden = config.projectNote === '';
            projectNote.querySelector('p').textContent = config.projectNote;
        };

        window.vormbyteCopyCliCode = function (id, button) {
            window.vormbyteClipboardCopy(document.getElementById(id).textContent).then(function () {
                var original = button.textContent;
                button.textContent = copiedLabel;
                setTimeout(function () { button.textContent = original; }, 1500);
            });
        };
        window.vormbyteCopyCliPrompt = function (button) {
            window.vormbyteCopyCliCode('vormbyte-cli-prompt', button);
        };
    }());
    </script>
    <?php
}

/**
 * Render the stable "Connect Your AI Client" container (Step 4). Its heading and
 * placeholder never disappear, so documentation and support can always refer to
 * the same step numbers while the selected route swaps the content below.
 */
function vormbyte_render_connect_client_section(
    ?string $new_password,
    ?string $existing_password,
    ?WP_Error $existing_error,
): void {
    $password_active = vormbyte_password_method_preselected($new_password, $existing_password, $existing_error);
    $has_password = $new_password !== null || $existing_password !== null;
    $rest_url = rest_url('mcp/vormbyte');
    // OAuth lives on its own MCP server so the canonical route above stays Application-Password-only
    // and untouched by the OAuth challenge. See vormbyte_register_oauth_mcp_server().
    $oauth_rest_url = rest_url('mcp/vormbyte-oauth');
    $username = wp_get_current_user()->user_login;
    $display_password = $new_password ?? $existing_password ?? 'YOUR-APP-PASSWORD';
    ?>
    <h2 class="vormbyte-step-heading">
        <span class="vormbyte-step-badge">4</span>
        <?php esc_html_e('Connect Your AI Tool', domain: 'vormbyte'); ?>
    </h2>
    <p id="vormbyte-step4-placeholder" class="description" style="margin:16px 0 0;">
        <?php esc_html_e(
            'Choose an AI tool and connection method above to see the setup instructions.',
            domain: 'vormbyte',
        ); ?>
    </p>
    <div class="vormbyte-method-panel" data-panel="cli" hidden>
        <?php vormbyte_render_cli_config_section(); ?>
    </div>
    <div class="vormbyte-method-panel" data-panel="oauth" hidden>
        <?php vormbyte_render_oauth_config_section($oauth_rest_url); ?>
    </div>
    <div class="vormbyte-method-panel" data-panel="password"<?php echo $password_active ? '' : ' hidden'; ?>>
        <?php if ($has_password): ?>
            <?php vormbyte_render_config_section($rest_url, $username, $display_password); ?>
        <?php endif; ?>
        <?php if (!$has_password): ?>
            <p class="description" style="margin:16px 0 0;"><?php esc_html_e(
                'Generate or enter the Application Password in step 3 to continue.',
                domain: 'vormbyte',
            ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render the Application Password card, shown inside Step 3 once that method is chosen.
 *
 * Just the generate button (with a collapsible name input) and a success notice after generation.
 * The list of existing passwords lives in the separate manage section at the bottom of the page.
 */
// Complexity is inherent: this is a single HTML template whose branches (password availability,
// newly generated vs. pasted vs. no password, has-existing toggles, error notices) each gate a
// distinct piece of inline markup. Splitting them into helpers would fragment one cohesive view.
// @mago-expect lint:cyclomatic-complexity
function vormbyte_render_password_step(
    ?string $new_password,
    ?string $existing_password = null,
    ?WP_Error $existing_error = null,
): void {
    $pw_status = vormbyte_app_passwords_status();
    $has_existing = vormbyte_get_mcp_passwords() !== [];
    $existing_section_open = $existing_password !== null || $existing_error !== null;
    ?>
    <p class="description" style="margin:0 0 12px;">
        <?php esc_html_e(
            'Generate an application password that your AI tool will use to authenticate with WordPress. The password is embedded into the connection text in step 4.',
            domain: 'vormbyte',
        ); ?>
    </p>

    <?php if (!$pw_status['available']): ?>
        <div class="notice notice-error inline" style="margin:12px 0 16px;">
            <p><strong><?php echo esc_html($pw_status['message']); ?></strong></p>
            <?php if ($pw_status['reason'] === 'unsupported' && vormbyte_likely_local_http()): ?>
                <p style="margin:8px 0 0;">
                    <?php esc_html_e(
                        'This site is on a local hostname over HTTP. Add this line to your wp-config.php (above the "/* That\'s all" comment), then reload:',
                        domain: 'vormbyte',
                    ); ?>
                </p>
                <pre style="background:#f6f7f7; border:1px solid #c3c4c7; padding:8px 12px; margin:6px 0 0; font-size:13px; border-radius:3px;">define('WP_ENVIRONMENT_TYPE', 'local');</pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($new_password !== null): ?>
        <div class="notice notice-success inline" style="margin:8px 0 16px;">
            <p style="margin:0 0 8px;"><?php esc_html_e(
                'Application password generated. It is now embedded in the connection text in step 4. Save it somewhere safe: it will not be shown in full again.',
                domain: 'vormbyte',
            ); ?></p>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <code id="vormbyte-new-pw-value" style="font-size:14px; font-weight:600; padding:6px 10px; background:#fff; border:1px solid #c3c4c7; border-radius:3px;"><?php echo
                    esc_html($new_password)
                ; ?></code>
                <button type="button" class="button button-small" onclick="vormbyteCopy('vormbyte-new-pw-value', this)">
                    <?php esc_html_e('Copy password only', domain: 'vormbyte'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($new_password === null && $existing_password !== null): ?>
        <div class="notice notice-success inline" style="margin:8px 0 16px;">
            <p style="margin:0;"><?php esc_html_e(
                'Password accepted. It is now embedded in the connection text in step 4.',
                domain: 'vormbyte',
            ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" style="margin: 0;">
        <?php wp_nonce_field('vormbyte_create_password'); ?>
        <input type="hidden" name="vormbyte_create_password" value="1" />
        <?php if (!$has_existing): ?>
            <p style="margin:0 0 10px;">
                <button
                    type="button"
                    class="button-link"
                    id="vormbyte-password-name-toggle"
                    aria-expanded="false"
                    aria-controls="vormbyte-password-name-field"
                    onclick="vormbyteTogglePasswordName(this)"
                ><?php esc_html_e('Customize password name (optional)', domain: 'vormbyte'); ?></button>
            </p>
        <?php endif; ?>
        <div
            id="vormbyte-password-name-field"
            <?php echo $has_existing ? '' : 'hidden'; ?>
            style="margin: 0 0 12px; <?php echo $has_existing ? '' : 'display:none;'; ?>"
        >
            <label for="vormbyte-password-name" style="display:block; margin-bottom:4px;">
                <strong><?php esc_html_e('Name', domain: 'vormbyte'); ?></strong>
            </label>
            <input
                type="text"
                id="vormbyte-password-name"
                name="vormbyte_password_name"
                placeholder="<?php esc_attr_e('e.g. Cursor on laptop, Claude Desktop', domain: 'vormbyte'); ?>"
                style="width:300px;"
                class="regular-text"
                maxlength="70"
            />
            <p class="description" style="margin-top:4px;">
                <?php esc_html_e(
                    'A label to identify this credential later. Leave blank to use "Vormbyte".',
                    domain: 'vormbyte',
                ); ?>
            </p>
        </div>
        <button
            type="submit"
            name="vormbyte_create_password"
            class="button button-primary"
            <?php echo !$pw_status['available'] ? 'disabled' : ''; ?>>
            <?php echo
                $has_existing
                    ? esc_html__('Generate another application password', domain: 'vormbyte')
                    : esc_html__('Generate application password', domain: 'vormbyte')
            ; ?>
        </button>
    </form>

    <p style="margin:14px 0 4px;">
        <button
            type="button"
            class="button-link"
            id="vormbyte-use-existing-toggle"
            aria-expanded="<?php echo $existing_section_open ? 'true' : 'false'; ?>"
            aria-controls="vormbyte-use-existing-field"
            onclick="vormbyteToggleUseExisting(this)"
        ><?php esc_html_e('I already have an application password', domain: 'vormbyte'); ?></button>
    </p>
    <div
        id="vormbyte-use-existing-field"
        <?php echo $existing_section_open ? '' : 'hidden'; ?>
        style="margin:6px 0 0; <?php echo $existing_section_open ? '' : 'display:none;'; ?>"
    >
        <form method="post" style="margin:0;">
            <?php wp_nonce_field('vormbyte_use_existing_password'); ?>
            <input type="hidden" name="vormbyte_use_existing_password" value="1" />
            <label for="vormbyte-existing-password" style="display:block; margin-bottom:4px;">
                <strong><?php esc_html_e('Paste the password value', domain: 'vormbyte'); ?></strong>
            </label>
            <input
                type="text"
                id="vormbyte-existing-password"
                name="vormbyte_existing_password"
                placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"
                style="width:340px; font-family:monospace;"
                class="regular-text"
                autocomplete="off"
            />
            <button type="submit" name="vormbyte_use_existing_password" class="button">
                <?php esc_html_e('Use this password', domain: 'vormbyte'); ?>
            </button>
            <?php if ($existing_error !== null): ?>
                <div class="notice notice-error inline" style="margin:8px 0 0;">
                    <p style="margin:0;"><?php echo esc_html($existing_error->get_error_message()); ?></p>
                </div>
            <?php endif; ?>
            <p class="description" style="margin-top:4px;">
                <?php esc_html_e(
                    'For reusing an application password you already saved (e.g. from a password manager). It is used only to fill the connection text and never stored on this site.',
                    domain: 'vormbyte',
                ); ?>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Build the paste-to-agent paragraph displayed in Option A of the Connect section.
 *
 * Returns a plain-text block the user can copy and paste into their AI tool.
 * The MCP server name uses the same placeholder as the JSON snippets so the live JS
 * preview can swap it in without re-rendering the page.
 */
function vormbyte_build_paste_to_agent_paragraph(
    string $rest_url,
    string $username,
    string $display_password,
    string $name_placeholder = '__VORMBYTE_MCP_NAME__',
    ?string $password_placeholder = null,
): string {
    $password_value = $password_placeholder ?? $display_password;
    $lines = [
        'I want to add this WordPress site as an MCP server to this AI tool.',
        '',
        'Connection details:',
        '- Server URL: ' . $rest_url,
        '- Username: ' . $username,
        '- Application password: ' . $password_value,
        '- Server name to use in the config: ' . $name_placeholder,
        '- Transport: @automattic/mcp-wordpress-remote via npx',
        '',
        'Setup rules:',
        '- Pass credentials ONLY as env vars: WP_API_URL, WP_API_USERNAME, WP_API_PASSWORD. Do NOT use CLI flags like --url or --password (the package ignores them).',
        '- args array must be exactly ["-y", "@automattic/mcp-wordpress-remote@latest"].'
            . (
                vormbyte_likely_self_signed_https()
                    ? "\n"
                    . '- Also set NODE_TLS_REJECT_UNAUTHORIZED="0" in env (this site uses a local self-signed TLS certificate).'
                    : ''
            ),
        '',
        'Don\'t ask me to confirm choices already specified above. After writing the config, restart or reload the MCP session (most clients require it), then verify by listing the server\'s tools. If it fails, show me the stderr from the npx process before proposing changes.',
        '',
        'If you cannot modify the config of this AI tool from here, tell me to expand "Manual configuration for your AI tool" on the Vormbyte Configuration page and copy the snippet manually.',
    ];

    return implode("\n", $lines);
}

/**
 * Build the npx server config array shared across multiple MCP clients.
 *
 * @param string $rest_url        MCP REST endpoint URL.
 * @param string $username        Current WordPress username.
 * @param string $display_password Plaintext password or placeholder.
 * @return array{command: string, args: list<string>, env: array<string, string>}
 */
function vormbyte_build_npx_server(string $rest_url, string $username, string $display_password): array
{
    $env = [
        'WP_API_URL' => $rest_url,
        'WP_API_USERNAME' => $username,
        'WP_API_PASSWORD' => $display_password,
    ];
    if (vormbyte_likely_self_signed_https()) {
        $env['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
    }
    return [
        'command' => 'npx',
        'args' => ['-y', '@automattic/mcp-wordpress-remote@latest'],
        'env' => $env,
    ];
}

/**
 * Build the MCPB bundle manifest (manifest.json contents) for this site.
 *
 * The bundle wraps the same npx proxy used by the JSON snippets, with the
 * connection credentials embedded directly so it installs without further
 * prompts. The plaintext application password is therefore written into the
 * file — callers must warn the user before offering the download.
 *
 * @param string $rest_url        MCP REST endpoint URL.
 * @param string $username        WordPress username.
 * @param string $display_password Plaintext application password.
 * @return array<string, mixed>
 */
function vormbyte_build_mcpb_manifest(
    string $rest_url,
    string $username,
    string $display_password,
    string $mcp_name,
): array {
    $env = [
        'WP_API_URL' => $rest_url,
        'WP_API_USERNAME' => $username,
        'WP_API_PASSWORD' => $display_password,
    ];
    if (vormbyte_likely_self_signed_https()) {
        $env['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
    }

    $site_name = vormbyte_unbranded_site_name(get_bloginfo('name'));
    $display_name = $site_name !== '' ? 'Vormbyte — ' . $site_name : 'Vormbyte';

    return [
        'manifest_version' => '0.3',
        'name' => $mcp_name,
        'display_name' => $display_name,
        'version' => VORMBYTE_VERSION,
        'description' => __(
            'Full WordPress control for your AI agent. Runs real PHP, queries the database, edits files — on your dev or staging site.',
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
                'args' => ['-y', '@automattic/mcp-wordpress-remote@latest'],
                'env' => $env,
            ],
        ],
    ];
}

/**
 * Stream a downloadable .mcpb bundle for Claude Desktop. Hooked on admin_post.
 *
 * The bundle embeds the plaintext application password, which WordPress only
 * exposes right after creation — so the password is posted back from the
 * connect page (where it was just shown) rather than read from storage.
 */
function vormbyte_handle_download_mcpb(): void
{
    if (!vormbyte_current_user_can_manage()) {
        wp_die(esc_html__('You are not allowed to download this bundle.', domain: 'vormbyte'));
    }

    check_admin_referer('vormbyte_download_mcpb');

    if (!class_exists('ZipArchive')) {
        wp_die(esc_html__(
            'Cannot build the bundle: the PHP zip extension is not available on this server. Use the JSON config instead.',
            domain: 'vormbyte',
        ));
    }

    $raw_password = $_POST['vormbyte_mcpb_password'] ?? '';
    $password = is_string($raw_password) ? (string) preg_replace('/\s+/', replacement: '', subject: $raw_password) : '';
    if ($password === '') {
        wp_die(esc_html__('Missing application password for the bundle.', domain: 'vormbyte'));
    }

    $username = wp_get_current_user()->user_login;
    $rest_url = rest_url('mcp/vormbyte');

    $raw_name = $_POST['vormbyte_mcpb_name'] ?? '';
    $mcp_name = is_string($raw_name)
        ? (string) preg_replace('/[^a-z0-9-]/', replacement: '', subject: strtolower($raw_name))
        : '';
    if ($mcp_name === '' || strlen($mcp_name) > 25) {
        $mcp_name = vormbyte_get_mcp_server_name_default();
    }

    $manifest = vormbyte_build_mcpb_manifest($rest_url, $username, $password, $mcp_name);
    $manifest_json = (string) wp_json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );

    $stub =
        "// Placeholder entry point. The actual MCP server is launched via mcp_config\n"
        . "// (npx @automattic/mcp-wordpress-remote), so this file is never executed.\n"
        . "// It exists only to satisfy the manifest's required entry_point field.\n";

    $tmp = wp_tempnam('vormbyte-mcpb');
    $zip = new ZipArchive();
    if ($tmp === '' || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        wp_die(esc_html__('Could not create the bundle archive.', domain: 'vormbyte'));
    }
    $zip->addFromString('manifest.json', $manifest_json);
    $zip->addFromString('server/index.js', $stub);
    $zip->close();

    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $filename = 'vormbyte-' . sanitize_file_name($host !== '' ? $host : 'site') . '.mcpb';

    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) filesize($tmp));
    readfile($tmp);
    wp_delete_file($tmp);
    exit();
}

/** @param array<string, mixed> $npx_server */
function vormbyte_build_zed_json(string $mcp_name, array $npx_server, int $opts): string
{
    return (string) json_encode([
        'context_servers' => [
            $mcp_name => array_merge([
                'source' => 'custom',
                'enabled' => true,
            ], $npx_server),
        ],
    ], $opts);
}

function vormbyte_build_opencode_json(
    string $mcp_name,
    string $rest_url,
    string $username,
    string $display_password,
    int $opts,
): string {
    $environment = [
        'WP_API_URL' => $rest_url,
        'WP_API_USERNAME' => $username,
        'WP_API_PASSWORD' => $display_password,
    ];
    if (vormbyte_likely_self_signed_https()) {
        $environment['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
    }
    return (string) json_encode([
        'mcp' => [
            $mcp_name => [
                'type' => 'local',
                'command' => ['npx', '-y', '@automattic/mcp-wordpress-remote@latest'],
                'environment' => $environment,
            ],
        ],
    ], $opts);
}

function vormbyte_build_codex_toml(
    string $mcp_name,
    string $rest_url,
    string $username,
    string $display_password,
): string {
    $esc = static fn(string $v): string => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';

    $lines = [
        '[mcp_servers.' . $mcp_name . ']',
        'command = "npx"',
        'args = ["-y", "@automattic/mcp-wordpress-remote@latest"]',
        '',
        '[mcp_servers.' . $mcp_name . '.env]',
        'WP_API_URL = ' . $esc($rest_url),
        'WP_API_USERNAME = ' . $esc($username),
        'WP_API_PASSWORD = ' . $esc($display_password),
    ];
    if (vormbyte_likely_self_signed_https()) {
        $lines[] = 'NODE_TLS_REJECT_UNAUTHORIZED = "0"';
    }
    return implode("\n", $lines);
}

function vormbyte_build_codex_cli_cmd(
    string $mcp_name,
    string $rest_url,
    string $username,
    string $display_password,
): string {
    $env = [
        'WP_API_URL' => $rest_url,
        'WP_API_USERNAME' => $username,
        'WP_API_PASSWORD' => $display_password,
    ];
    if (vormbyte_likely_self_signed_https()) {
        $env['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
    }
    return vormbyte_codex_stdio_add_command($mcp_name, $env, command: 'npx -y @automattic/mcp-wordpress-remote@latest');
}

function vormbyte_build_claude_code_cmd(
    string $mcp_name,
    string $rest_url,
    string $username,
    string $display_password,
): string {
    $sq = static fn(string $v): string => "'" . str_replace(search: "'", replace: "'\\''", subject: $v) . "'";

    // --env/-e consumes multiple values. Put the server name first so Claude Code does not parse
    // it as an environment variable and reject it for not matching KEY=value.
    $parts = [
        'claude mcp add',
        '--scope user',
        $sq($mcp_name),
        '-e WP_API_URL=' . $sq($rest_url),
        '-e WP_API_USERNAME=' . $sq($username),
        '-e WP_API_PASSWORD=' . $sq($display_password),
    ];
    if (vormbyte_likely_self_signed_https()) {
        $parts[] = '-e NODE_TLS_REJECT_UNAUTHORIZED=' . $sq('0');
    }
    $parts[] = '-- npx -y @automattic/mcp-wordpress-remote@latest';

    return implode(" \\\n  ", $parts);
}

/**
 * Build all per-client, per-transport config entries.
 *
 * @param string $rest_url        MCP REST endpoint URL.
 * @param string $username        Current WordPress username.
 * @param string $display_password Plaintext password or placeholder.
 * @param string $mcp_name        MCP server name used as the config key.
 * @return array<string, array{code: string, hint: string, paths: array<string, string>, isShell: bool}>
 */
function vormbyte_build_configs(string $rest_url, string $username, string $display_password, string $mcp_name): array
{
    $opts = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
    $npx_server = vormbyte_build_npx_server($rest_url, $username, $display_password);
    $mcp_servers_json = (string) json_encode(['mcpServers' => [$mcp_name => $npx_server]], $opts);
    $vscode_servers_json = (string) json_encode(['servers' => [$mcp_name => $npx_server]], $opts);

    /* translators: %s: config file name wrapped in <code> tags */
    $add_to = __('Add to %s.', domain: 'vormbyte');

    $special = [
        'claude-code' => [
            'code' => vormbyte_build_claude_code_cmd($mcp_name, $rest_url, $username, $display_password),
            'hint' => __(
                'Run in your terminal. This registers the server for your user, so it is available in all your projects.',
                domain: 'vormbyte',
            ),
            'paths' => [],
            'isShell' => true,
        ],
        'codex-app' => [
            'code' => vormbyte_build_codex_toml($mcp_name, $rest_url, $username, $display_password),
            'hint' => sprintf(
                /* translators: %s: config.toml file name wrapped in <code> tags */
                __('Add to %s. ChatGPT Desktop and Codex CLI share this user configuration.', domain: 'vormbyte'),
                '<code>config.toml</code>',
            ),
            'paths' => [
                'macOS / Linux' => '~/.codex/config.toml',
                'Windows' => '%USERPROFILE%\\.codex\\config.toml',
            ],
            'isShell' => false,
        ],
        'codex-cli' => [
            'code' => vormbyte_build_codex_cli_cmd($mcp_name, $rest_url, $username, $display_password),
            'hint' => __(
                'Run in your terminal. Codex saves the server in your user configuration, so it is available in every project.',
                domain: 'vormbyte',
            ),
            'paths' => [],
            'isShell' => true,
        ],
        'zed' => [
            'code' => vormbyte_build_zed_json($mcp_name, $npx_server, $opts),
            'hint' => sprintf($add_to, '<code>settings.json</code>'),
            'paths' => ['macOS / Linux' => '~/.config/zed/settings.json'],
            'isShell' => false,
        ],
        'opencode' => [
            'code' => vormbyte_build_opencode_json($mcp_name, $rest_url, $username, $display_password, $opts),
            'hint' => sprintf($add_to, '<code>opencode.json</code>'),
            'paths' => [
                __('Project', domain: 'vormbyte') => 'opencode.json',
                __('Global', domain: 'vormbyte') => '~/.config/opencode/opencode.json',
            ],
            'isShell' => false,
        ],
        // Grok Bot has no config file of its own: it reads the prompt above and connects itself,
        // so there is no manual snippet to show (the "Manual configuration" section stays hidden
        // for it, see vormbyteSetClient() on the Configuration page).
        'grok-bot' => ['code' => '', 'hint' => '', 'paths' => [], 'isShell' => false],
    ];

    return array_merge(vormbyte_build_standard_configs($mcp_servers_json, $vscode_servers_json), $special);
}

/**
 * Build per-client config entries that reuse the standard mcpServers/servers JSON payloads.
 *
 * @return array<string, array{code: string, hint: string, paths: array<string, string>, isShell: bool}>
 */
function vormbyte_build_standard_configs(string $mcp_servers_json, string $vscode_servers_json): array
{
    /* translators: %s: config file name wrapped in <code> tags */
    $add_to = __('Add to %s.', domain: 'vormbyte');

    return [
        'claude-desktop' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>claude_desktop_config.json</code>'),
            'paths' => [
                'macOS' => '~/Library/Application Support/Claude/claude_desktop_config.json',
                'Windows' => '%APPDATA%\\Claude\\claude_desktop_config.json',
            ],
            'isShell' => false,
        ],
        'cursor' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp.json</code>'),
            'paths' => [
                __('Global', domain: 'vormbyte') => '~/.cursor/mcp.json',
                __('Project', domain: 'vormbyte') => '.cursor/mcp.json',
            ],
            'isShell' => false,
        ],
        'vscode' => [
            'code' => $vscode_servers_json,
            'hint' => sprintf($add_to, '<code>mcp.json</code>'),
            'paths' => [
                __('Workspace', domain: 'vormbyte') => '.vscode/mcp.json',
                __('User', domain: 'vormbyte') => __(
                    'Run: MCP: Open User Configuration (command palette)',
                    domain: 'vormbyte',
                ),
            ],
            'isShell' => false,
        ],
        'windsurf' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp_config.json</code>'),
            'paths' => [
                'macOS / Linux' => '~/.codeium/windsurf/mcp_config.json',
                'Windows' => '%USERPROFILE%\\.codeium\\windsurf\\mcp_config.json',
            ],
            'isShell' => false,
        ],
        'cline' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>cline_mcp_settings.json</code>'),
            'paths' => [
                __('Via UI', domain: 'vormbyte') => __(
                    'Cline sidebar → MCP Servers → Configure MCP Servers',
                    domain: 'vormbyte',
                ),
            ],
            'isShell' => false,
        ],
        'roo-code' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp.json</code>'),
            'paths' => [
                __('Project', domain: 'vormbyte') => '.roo/mcp.json',
                __('Via UI', domain: 'vormbyte') => __(
                    'Roo Code sidebar → MCP Servers → Configure MCP Servers',
                    domain: 'vormbyte',
                ),
            ],
            'isShell' => false,
        ],
        'kilo-code' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp.json</code>'),
            'paths' => [
                __('Project', domain: 'vormbyte') => '.kilocode/mcp.json',
                __('Via UI', domain: 'vormbyte') => __(
                    'Kilo Code sidebar → MCP Servers → Configure MCP Servers',
                    domain: 'vormbyte',
                ),
            ],
            'isShell' => false,
        ],
        'github-copilot' => [
            'code' => $vscode_servers_json,
            'hint' => sprintf($add_to, '<code>mcp.json</code>'),
            'paths' => [
                __('Project', domain: 'vormbyte') => '.github/copilot/mcp.json',
            ],
            'isShell' => false,
        ],
        'amazon-q' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp.json</code>'),
            'paths' => [
                __('Global', domain: 'vormbyte') => '~/.aws/amazonq/mcp.json',
                __('Project', domain: 'vormbyte') => '.amazonq/mcp.json',
            ],
            'isShell' => false,
        ],
        'antigravity' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp_config.json</code>'),
            'paths' => [
                __('Global (macOS / Linux)', domain: 'vormbyte') => '~/.gemini/config/mcp_config.json',
                __('Global (Windows)', domain: 'vormbyte') => '%USERPROFILE%\\.gemini\\config\\mcp_config.json',
                __('Workspace', domain: 'vormbyte') => '.agents/mcp_config.json',
            ],
            'isShell' => false,
        ],
        'antigravity-cli' => [
            'code' => $mcp_servers_json,
            'hint' => sprintf($add_to, '<code>mcp_config.json</code>'),
            'paths' => [
                __('Global (macOS / Linux)', domain: 'vormbyte') => '~/.gemini/config/mcp_config.json',
                __('Global (Windows)', domain: 'vormbyte') => '%USERPROFILE%\\.gemini\\config\\mcp_config.json',
                __('Workspace', domain: 'vormbyte') => '.agents/mcp_config.json',
            ],
            'isShell' => false,
        ],
    ];
}

/**
 * Informational notice above the connect prompt: pasting the prompt hands the
 * application password to the AI agent. Links to the manual configuration,
 * which reaches the same result without exposing the password to the AI.
 */
function vormbyte_render_prompt_password_notice(): void
{ ?>
    <div id="vormbyte-prompt-password-notice" class="notice notice-info inline" style="margin:0 0 12px;">
        <p style="margin:0;">
            <strong><?php esc_html_e(
                'This prompt shares your application password with your AI agent.',
                domain: 'vormbyte',
            ); ?></strong>
            <?php printf(
                /* translators: %s: link that opens the manual configuration section */
                esc_html__(
                    'Prefer to keep it private? Use the %s and paste the snippet into the config file yourself.',
                    domain: 'vormbyte',
                ),
                '<button type="button" class="button-link" onclick="vormbyteOpenManualConfig()">'
                . esc_html__('manual configuration', domain: 'vormbyte')
                . '</button>',
            ); ?>
        </p>
    </div>
    <?php }

/**
 * Render the "download .mcpb bundle" option (shown only for the Claude Desktop
 * tab via JS). Hidden when no real password is available, since the bundle must
 * embed the plaintext password. Warns the user that the file carries it.
 */
function vormbyte_render_mcpb_download(string $display_password, string $mcp_name): void
{
    // Without the zip extension the download handler can't build the bundle, so
    // omit the option entirely rather than send the user to an error page.
    if (!class_exists('ZipArchive')) {
        return;
    }
    $confirm_msg = wp_json_encode(__(
        'This bundle contains your password. The .mcpb file embeds your application password in plaintext so it installs without prompts. Anyone who gets the file can control this site — don\'t share it, and delete it after installing.',
        domain: 'vormbyte',
    ));
    $confirm_msg = $confirm_msg !== false ? $confirm_msg : '""';
    ?>
    <div id="vormbyte-mcpb-download" style="display:none; margin-top:20px; margin-bottom:4px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;">
            <input type="hidden" name="action" value="vormbyte_download_mcpb">
            <?php wp_nonce_field('vormbyte_download_mcpb'); ?>
            <input type="hidden" name="vormbyte_mcpb_password" value="<?php echo esc_attr($display_password); ?>">
            <input type="hidden" name="vormbyte_mcpb_name" id="vormbyte-mcpb-name" value="<?php echo
                esc_attr($mcp_name)
            ; ?>">
            <button
                type="submit"
                class="button button-primary"
                style="display:inline-flex; flex-direction:column; align-items:flex-start; width:auto; padding:12px 24px; height:auto; gap:3px;"
                onclick="return confirm(<?php echo esc_attr($confirm_msg); ?>)"
            ><span style="font-size:15px; line-height:1.2;"><?php esc_html_e(
                'Download .mcpb bundle',
                domain: 'vormbyte',
            ); ?></span><span style="font-size:12px; font-weight:400; opacity:0.88; line-height:1.2;"><?php esc_html_e(
                'Requires Claude Desktop 1.24012.1 or later',
                domain: 'vormbyte',
            ); ?></span></button>
        </form>
        <p style="margin:8px 0 4px;">
            <button
                type="button"
                class="button-link"
                onclick="vormbyteShowPromptForDesktop(this)"
            ><?php esc_html_e('Use the prompt for Claude Desktop instead', domain: 'vormbyte'); ?></button>
        </p>
    </div>
    <?php
}

/** Render the JSON config block. */
function vormbyte_render_json_config_block(): void
{ ?>
    <div class="vormbyte-tab-content" style="border-radius:4px;">
        <div class="vormbyte-config-block">
            <pre id="vormbyte-config-code"></pre>
            <button type="button" class="button vormbyte-copy-btn" onclick="vormbyteCopyConfig(this)"><?php esc_html_e(
                'Copy',
                domain: 'vormbyte',
            ); ?></button>
        </div>
        <div id="vormbyte-config-footer" style="font-size:13px; color:#666; border-top: 1px solid #c3c4c7;">
            <div id="vormbyte-config-merge-note" style="padding: 10px 16px 0;">
                <?php esc_html_e(
                    'If your config file already has content, merge this into your existing config instead of replacing it.',
                    domain: 'vormbyte',
                ); ?>
            </div>
            <div id="vormbyte-config-hint" style="padding: 10px 16px;"></div>
            <div id="vormbyte-config-paths" style="padding: 0 16px 10px;"></div>
        </div>
    </div>
    <?php }

/**
 * Render the tabbed MCP client config section.
 *
 * @param string $rest_url        MCP REST endpoint URL.
 * @param string $username        Current WordPress username.
 * @param string $display_password Plaintext password or placeholder.
 */
function vormbyte_render_config_section(string $rest_url, string $username, string $display_password): void
{
    $default_name = vormbyte_get_mcp_server_name_default();
    $name_placeholder = '__VORMBYTE_MCP_NAME__';
    $pw_slot = '__VORMBYTE_PW_SLOT__';
    $password_is_placeholder = hash_equals('YOUR-APP-PASSWORD', $display_password);
    $configs = vormbyte_build_configs($rest_url, $username, $display_password, $name_placeholder);
    $configs_json = (string) wp_json_encode($configs);

    $clients = array_intersect_key(vormbyte_ai_clients(), $configs);

    $copied_label = esc_js(__('Copied!', domain: 'vormbyte'));
    $paste_paragraph_initial = vormbyte_build_paste_to_agent_paragraph(
        $rest_url,
        $username,
        $display_password,
        $default_name,
    );
    $paste_paragraph_template = vormbyte_build_paste_to_agent_paragraph(
        $rest_url,
        $username,
        $display_password,
        $name_placeholder,
        $pw_slot,
    );
    ?>
    <div id="vormbyte-connect-content" style="display:none; margin-top:16px;">

    <?php vormbyte_render_local_https_notice(); ?>

    <?php if (!$password_is_placeholder) {
        vormbyte_render_mcpb_download($display_password, $default_name);
    } ?>

    <?php vormbyte_render_prompt_password_notice(); ?>

    <div class="vormbyte-paste-block" id="vormbyte-paste-block" style="display:none;">
        <div class="vormbyte-paste-content" id="vormbyte-paste-content">
            <pre id="vormbyte-paste-text"><?php echo esc_html($paste_paragraph_initial); ?></pre>
        </div>
        <div class="vormbyte-paste-actions">
            <button
                type="button"
                class="button-link"
                id="vormbyte-paste-expand"
                onclick="vormbyteToggleExpandPaste(this)"
                aria-expanded="false"
                aria-controls="vormbyte-paste-content"
            ><?php esc_html_e('Show full text', domain: 'vormbyte'); ?></button>
            <button
                type="button"
                class="button button-primary"
                onclick="vormbyteCopyPaste(this)"
            ><?php esc_html_e('Copy prompt', domain: 'vormbyte'); ?></button>
            <p
                id="vormbyte-paste-copied-warning"
                style="display:none; margin:0; color:#d63638; font-size:13px; font-weight:600;"
            >
                <?php esc_html_e(
                    "Don't share with anyone: it contains an application password that grants access to this WordPress site.",
                    domain: 'vormbyte',
                ); ?>
            </p>
        </div>
    </div>

    <p style="margin:6px 0 4px;">
        <button
            type="button"
            class="button-link"
            id="vormbyte-server-name-toggle"
            aria-expanded="false"
            aria-controls="vormbyte-server-name-field"
            onclick="vormbyteToggleServerName(this)"
        ><?php esc_html_e('Change server name (optional)', domain: 'vormbyte'); ?></button>
    </p>
    <div id="vormbyte-server-name-field" hidden style="display:none; margin: 6px 0 14px;">
        <input
            type="text"
            id="vormbyte-mcp-name"
            value="<?php echo esc_attr($default_name); ?>"
            placeholder="<?php echo esc_attr($default_name); ?>"
            maxlength="25"
            style="width:220px;"
            oninput="vormbyteUpdateName(this.value)"
        >
        <p class="description" style="margin:6px 0 0;">
            <?php esc_html_e(
                'Give the server a name you’ll recognize. The connection text and snippets below update as you type.',
                domain: 'vormbyte',
            ); ?>
        </p>
        <div id="vormbyte-name-warning" class="notice notice-warning inline" style="display:none; margin:8px 0 0;">
            <p style="margin:0;">
                <?php esc_html_e(
                    'Maximum 25 characters reached. Required for tool compatibility.',
                    domain: 'vormbyte',
                ); ?>
            </p>
        </div>
        <div id="vormbyte-name-suggestion" class="notice notice-warning inline" style="display:none; margin:8px 0 0;">
            <p style="margin:0;">
                <?php esc_html_e(
                    'Tip: keep "vormbyte" in the name so you (and your AI agent) can tell this MCP server apart from others.',
                    domain: 'vormbyte',
                ); ?>
            </p>
        </div>
    </div>

    <div id="vormbyte-manual-btn-wrap" style="display:none;">
        <hr style="border:none; border-top:1px solid #dcdcde; margin:12px 0 8px;">
        <button
            type="button"
            class="button button-secondary"
            id="vormbyte-manual-toggle"
            aria-expanded="false"
            aria-controls="vormbyte-manual-config"
            onclick="vormbyteToggleManualConfig(this)"
        ><?php esc_html_e('Manual configuration for your AI tool', domain: 'vormbyte'); ?></button>
    </div>

    <div id="vormbyte-manual-config" hidden style="display:none; margin-top:14px;">
        <?php vormbyte_render_json_config_block(); ?>
        <p style="margin:10px 0 4px;">
            <button
                type="button"
                class="button-link"
                id="vormbyte-npxless-toggle"
                aria-expanded="false"
                aria-controls="vormbyte-npxless-config"
                onclick="vormbyteToggleNpxlessConfig(this)"
            ><?php esc_html_e(
                'Configs above not working? Try this npx-free alternative.',
                domain: 'vormbyte',
            ); ?></button>
        </p>
    </div>

    <div id="vormbyte-npxless-config" hidden style="display:none;">
        <p class="description" style="margin:0 0 12px;">
            <?php esc_html_e(
                'Copy this configuration snippet to connect using direct HTTP (no Node/npx required).',
                domain: 'vormbyte',
            ); ?>
        </p>

        <div class="vormbyte-client-tabs">
            <button
                type="button"
                class="vormbyte-client-tab vormbyte-npxless-client-tab active"
                onclick="vormbyteSetNpxlessClient('claude', this)"
            ><?php esc_html_e('Claude Code', domain: 'vormbyte'); ?></button>
            <button
                type="button"
                class="vormbyte-client-tab vormbyte-npxless-client-tab"
                onclick="vormbyteSetNpxlessClient('codex', this)"
            ><?php esc_html_e('Codex app / CLI', domain: 'vormbyte'); ?></button>
        </div>

        <div class="vormbyte-tab-content" style="border-radius:4px;">
            <div class="vormbyte-config-block">
                <pre id="vormbyte-npxless-code"></pre>
                <button type="button" class="button vormbyte-copy-btn" onclick="vormbyteCopyNpxlessConfig(this)"><?php esc_html_e(
                    'Copy',
                    domain: 'vormbyte',
                ); ?></button>
            </div>
            <div id="vormbyte-npxless-footer" style="font-size:13px; color:#666; border-top: 1px solid #c3c4c7;">
                <div id="vormbyte-npxless-hint" style="padding: 10px 16px;">
                    <?php esc_html_e('Add to your project’s .mcp.json file.', domain: 'vormbyte'); ?>
                </div>
                <div id="vormbyte-npxless-paths" style="padding: 0 16px 10px;"></div>
            </div>
        </div>
    </div>

    </div><!-- #vormbyte-connect-content -->

    <script>
    (function () {
        var configs = <?php echo $configs_json; ?>;
        var clientLabels = <?php echo wp_json_encode($clients); ?>;
        var client = '';
        var defaultName = <?php echo wp_json_encode($default_name); ?>;
        var pasteTemplate = <?php echo wp_json_encode($paste_paragraph_template); ?>;
        var mcpName = <?php echo wp_json_encode($default_name); ?>;
        var npxlessClient = 'claude';
        var namePlaceholder = <?php echo wp_json_encode($name_placeholder); ?>;
        var passwordSentinel = <?php echo wp_json_encode($pw_slot); ?>;
        var passwordValue = <?php echo wp_json_encode($display_password); ?>;
        var passwordIsPlaceholder = <?php echo wp_json_encode($password_is_placeholder); ?>;
        var usernameValue = <?php echo wp_json_encode($username); ?>;

        function renderPaste() {
            var text = pasteTemplate.split(namePlaceholder).join(mcpName);
            var container = document.getElementById('vormbyte-paste-text');
            container.textContent = '';
            var idx = text.indexOf(passwordSentinel);
            if (idx === -1) {
                container.appendChild(document.createTextNode(text));
                return;
            }
            container.appendChild(document.createTextNode(text.substring(0, idx)));
            if (passwordIsPlaceholder) {
                var span = document.createElement('span');
                span.className = 'vormbyte-placeholder';
                span.textContent = 'YOUR-APP-PASSWORD';
                container.appendChild(span);
            } else {
                container.appendChild(document.createTextNode(passwordValue));
            }
            container.appendChild(document.createTextNode(text.substring(idx + passwordSentinel.length)));
        }

        function render() {
            renderConfig();
            renderPaste();
            renderNpxlessConfig();
        }

        function renderConfig() {
            if (!client) { return; }
            var cfg = configs[client];
            if (!cfg) { return; }

            var code = cfg.code.split(namePlaceholder).join(mcpName);
            var codeEl = document.getElementById('vormbyte-config-code');
            codeEl.textContent = code;
            if (code.indexOf('YOUR-APP-PASSWORD') !== -1) {
                codeEl.innerHTML = codeEl.innerHTML.replace(
                    /YOUR-APP-PASSWORD/g,
                    '<span class="vormbyte-placeholder">YOUR-APP-PASSWORD</span>'
                );
            }
            document.getElementById('vormbyte-config-hint').innerHTML = cfg.hint;

            var mergeNote = document.getElementById('vormbyte-config-merge-note');
            if (mergeNote) { mergeNote.style.display = cfg.isShell ? 'none' : ''; }

            var isDesktop = client === 'claude-desktop';
            var noManualConfig = client === 'grok-bot';
            var mcpbEl = document.getElementById('vormbyte-mcpb-download');
            if (mcpbEl) { mcpbEl.style.display = isDesktop ? '' : 'none'; }
            var pasteBlock = document.getElementById('vormbyte-paste-block');
            if (pasteBlock) { pasteBlock.style.display = isDesktop ? 'none' : ''; }
            var pwNotice = document.getElementById('vormbyte-prompt-password-notice');
            // The notice's only purpose is to point at the manual config as a privacy-preserving
            // alternative; a client with none (Grok Bot has no config file) has nothing to point at.
            if (pwNotice) { pwNotice.style.display = isDesktop || noManualConfig ? 'none' : ''; }
            var manualBtnWrap = document.getElementById('vormbyte-manual-btn-wrap');
            if (manualBtnWrap) { manualBtnWrap.style.display = noManualConfig ? 'none' : ''; }
            var npxlessToggle = document.getElementById('vormbyte-npxless-toggle');
            if (npxlessToggle) {
                var showNpxless = client === 'claude-code' || client === 'codex-app' || client === 'codex-cli';
                npxlessToggle.parentElement.style.display = showNpxless ? '' : 'none';
                if (!showNpxless) {
                    var npxlessConfig = document.getElementById('vormbyte-npxless-config');
                    if (npxlessConfig) { npxlessConfig.style.display = 'none'; npxlessConfig.hidden = true; }
                    npxlessToggle.setAttribute('aria-expanded', 'false');
                }
            }

            var pathsEl = document.getElementById('vormbyte-config-paths');
            var keys = Object.keys(cfg.paths);
            if (keys.length > 0) {
                var html = '<ul style="margin:4px 0 0; padding-left:20px;">';
                keys.forEach(function (label) {
                    html += '<li><strong>' + label + '</strong>: <code>' + cfg.paths[label] + '</code></li>';
                });
                html += '</ul>';
                pathsEl.innerHTML = html;
                pathsEl.style.display = '';
            } else {
                pathsEl.innerHTML = '';
                pathsEl.style.display = 'none';
            }
        }

        window.vormbyteSetClient = function (key) {
            if (!configs[key]) { return; }
            client = key;
            var content = document.getElementById('vormbyte-connect-content');
            if (content) { content.style.display = ''; }
            var manualToggle = document.getElementById('vormbyte-manual-toggle');
            if (manualToggle && clientLabels[key]) {
                manualToggle.textContent = <?php echo
                    wp_json_encode(__('Manual configuration for', domain: 'vormbyte'))
                ; ?> + ' ' + clientLabels[key];
            }
            renderConfig();
        };

        window.vormbyteSetNpxlessClient = function (key, btn) {
            npxlessClient = key;
            document.querySelectorAll('.vormbyte-npxless-client-tab').forEach(function (t) { t.classList.remove('active'); });
            btn.classList.add('active');
            renderNpxlessConfig();
        };

        function updateNameWarning(value) {
            var warning = document.getElementById('vormbyte-name-warning');
            warning.style.display = value.length >= 25 ? 'block' : 'none';

            var suggestion = document.getElementById('vormbyte-name-suggestion');
            var trimmed = value.trim();
            var missingVormbyte = trimmed.length > 0 && trimmed.toLowerCase().indexOf('vormbyte') === -1;
            suggestion.style.display = missingVormbyte ? 'block' : 'none';
        }

        window.vormbyteUpdateName = function (value) {
            mcpName = value.trim() || defaultName;
            var nameField = document.getElementById('vormbyte-mcpb-name');
            if (nameField) { nameField.value = mcpName; }
            updateNameWarning(value);
            render();
        };

        window.vormbyteShowPromptForDesktop = function (btn) {
            var mcpbEl = document.getElementById('vormbyte-mcpb-download');
            if (mcpbEl) { mcpbEl.style.display = 'none'; }
            var pasteBlock = document.getElementById('vormbyte-paste-block');
            if (pasteBlock) { pasteBlock.style.display = ''; }
            var pwNotice = document.getElementById('vormbyte-prompt-password-notice');
            if (pwNotice) { pwNotice.style.display = ''; }
        };

        window.vormbyteToggleServerName = function (btn) {
            var field = document.getElementById('vormbyte-server-name-field');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                field.style.display = 'none';
                field.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
            } else {
                field.style.display = 'block';
                field.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                var input = document.getElementById('vormbyte-mcp-name');
                if (input) { input.focus(); }
            }
        };

        window.vormbyteToggleManualConfig = function (btn) {
            var panel = document.getElementById('vormbyte-manual-config');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                panel.style.display = 'none';
                panel.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
            } else {
                panel.style.display = '';
                panel.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        // Open the manual-config section (never closes it) and scroll to it.
        // Used by the "manual configuration" link in the password notice.
        window.vormbyteOpenManualConfig = function () {
            var panel = document.getElementById('vormbyte-manual-config');
            if (panel === null) {
                return;
            }
            panel.style.display = '';
            panel.hidden = false;
            var toggle = document.getElementById('vormbyte-manual-toggle');
            if (toggle !== null) {
                toggle.setAttribute('aria-expanded', 'true');
            }
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        window.vormbyteToggleExpandPaste = function (btn) {
            var content = document.getElementById('vormbyte-paste-content');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                content.classList.remove('is-expanded');
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = <?php echo wp_json_encode(__('Show full text', domain: 'vormbyte')); ?>;
            } else {
                content.classList.add('is-expanded');
                btn.setAttribute('aria-expanded', 'true');
                btn.textContent = <?php echo wp_json_encode(__('Show less', domain: 'vormbyte')); ?>;
            }
        };

        window.vormbyteCopyPaste = function (btn) {
            window.vormbyteClipboardCopy(document.getElementById('vormbyte-paste-text').textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = '<?php echo $copied_label; ?>';
                var warning = document.getElementById('vormbyte-paste-copied-warning');
                if (warning) { warning.style.display = 'block'; }
                setTimeout(function () {
                    btn.textContent = orig;
                    if (warning) { warning.style.display = 'none'; }
                }, 4000);
            });
        };

        window.vormbyteCopyConfig = function (btn) {
            window.vormbyteClipboardCopy(document.getElementById('vormbyte-config-code').textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = '<?php echo $copied_label; ?>';
                setTimeout(function () { btn.textContent = orig; }, 1500);
            });
        };

        window.vormbyteToggleNpxlessConfig = function (btn) {
            var panel = document.getElementById('vormbyte-npxless-config');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                panel.style.display = 'none';
                panel.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
            } else {
                panel.style.display = '';
                panel.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
            }
        };

        window.vormbyteCopyNpxlessConfig = function (btn) {
            window.vormbyteClipboardCopy(document.getElementById('vormbyte-npxless-code').textContent).then(function () {
                var orig = btn.textContent;
                btn.textContent = '<?php echo $copied_label; ?>';
                setTimeout(function () { btn.textContent = orig; }, 1500);
            });
        };

        function renderNpxlessConfig() {
            var npxlessCodeEl = document.getElementById('vormbyte-npxless-code');
            if (!npxlessCodeEl) { return; }

            var serverName = mcpName;
            var url = <?php echo wp_json_encode($rest_url); ?>;
            var username = usernameValue;

            var authHeaderValue;
            if (passwordIsPlaceholder) {
                authHeaderValue = 'Basic <span class="vormbyte-placeholder">BASE64_ENCODED_CREDENTIALS</span>';
            } else {
                var pwClean = passwordValue.replace(/\s+/g, '');
                var encoded = window.btoa(username + ':' + pwClean);
                authHeaderValue = 'Basic ' + encoded;
            }

            var indent = '  ';
            var hintEl = document.getElementById('vormbyte-npxless-hint');
            var pathsEl = document.getElementById('vormbyte-npxless-paths');
            var placeholder = 'BASE64_ENCODED_CREDENTIALS';
            var jsonQuote = function (value) {
                return JSON.stringify(value);
            };
            var tomlQuote = function (value) {
                return '"' + value.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
            };
            var code;

            if (npxlessClient === 'codex') {
                code = '[mcp_servers.' + serverName + ']\n' +
                    'url = ' + tomlQuote(url) + '\n' +
                    'http_headers = { Authorization = ' + tomlQuote(authHeaderValue.replace(/<[^>]+>/g, '')) + ' }';
                hintEl.textContent = <?php echo
                    wp_json_encode(__('Add to your project’s .codex/config.toml file.', domain: 'vormbyte'))
                ; ?>;
                pathsEl.innerHTML = '<ul style="margin:4px 0 0; padding-left:20px;">' +
                    '<li><strong><?php echo
                        esc_js(__('Project', domain: 'vormbyte'))
                    ; ?></strong>: <code>.codex/config.toml</code></li>' +
                    '<li><strong><?php echo
                        esc_js(__('Global', domain: 'vormbyte'))
                    ; ?></strong>: <code>~/.codex/config.toml</code></li>' +
                    '</ul>';
            } else {
                code = '{\n' +
                    indent + '"mcpServers": {\n' +
                    indent + indent + jsonQuote(serverName) + ': {\n' +
                    indent + indent + indent + '"type": "http",\n' +
                    indent + indent + indent + '"url": ' + jsonQuote(url) + ',\n' +
                    indent + indent + indent + '"headers": {\n' +
                    indent + indent + indent + indent + '"Authorization": ' + jsonQuote(authHeaderValue.replace(/<[^>]+>/g, '')) + '\n' +
                    indent + indent + indent + '}\n' +
                    indent + indent + '}\n' +
                    indent + '}\n' +
                    '}';
                hintEl.textContent = <?php echo
                    wp_json_encode(__('Add to your project’s .mcp.json file.', domain: 'vormbyte'))
                ; ?>;
                pathsEl.innerHTML = '<ul style="margin:4px 0 0; padding-left:20px;">' +
                    '<li><strong><?php echo
                        esc_js(__('Project', domain: 'vormbyte'))
                    ; ?></strong>: <code>.mcp.json</code></li>' +
                    '</ul>';
            }

            npxlessCodeEl.textContent = code;
            if (passwordIsPlaceholder) {
                npxlessCodeEl.innerHTML = npxlessCodeEl.innerHTML.replace(
                    placeholder,
                    '<span class="vormbyte-placeholder">' + placeholder + '</span>'
                );
            }
        }

        render();
    }());
    </script>
    <?php
}

function vormbyte_render_mcp_dependency_inline_notice(?WP_Error $dependency_error): void
{
    if ($dependency_error === null) {
        return;
    }

    ?>
    <div class="vormbyte-mcp-error-panel" role="alert">
        <h2><?php esc_html_e('Vormbyte cannot expose MCP', domain: 'vormbyte'); ?></h2>
        <p><?php echo esc_html($dependency_error->get_error_message()); ?></p>
    </div>
    <?php
}

/**
 * Warn when the web server does not forward HTTP Authorization headers to PHP.
 */
function vormbyte_render_authorization_header_warning(): void
{
    if (wp_is_site_protected_by_basic_auth()) {
        return;
    }

    $test_url = rest_url('wp-site-health/v1/tests/authorization-header');
    $rest_nonce = (string) wp_create_nonce('wp_rest');
    ?>
    <div id="vormbyte-authorization-header-warning" class="notice notice-warning vormbyte-keep" role="alert" hidden>
        <p>
            <strong><?php esc_html_e(
                'The HTTP Authorization header is not reaching PHP.',
                domain: 'vormbyte',
            ); ?></strong>
            <?php esc_html_e(
                'Application Password authentication may fail with unexpected 401 responses even when the credentials are correct.',
                domain: 'vormbyte',
            ); ?>
        </p>
        <p>
            <?php esc_html_e(
                'For Apache, add this directive to the applicable virtual host or .htaccess configuration, then reload the server:',
                domain: 'vormbyte',
            ); ?>
            <code>SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1</code>
            <?php esc_html_e(
                'If you cannot change the server configuration, contact your hosting provider.',
                domain: 'vormbyte',
            ); ?>
        </p>
    </div>
    <script>
    window.fetch(<?php echo wp_json_encode($test_url); ?>, {
        credentials: 'same-origin',
        headers: {
            'Authorization': 'Basic dXNlcjpwd2Q=',
            'X-WP-Nonce': <?php echo wp_json_encode($rest_nonce); ?>
        }
    }).then(function (response) {
        if (!response.ok) {
            throw new Error('Authorization header test unavailable');
        }
        return response.json();
    }).then(function (result) {
        if (result && result.status !== 'good') {
            document.getElementById('vormbyte-authorization-header-warning').hidden = false;
        }
    }).catch(function () {
        // A REST or network failure does not prove that Authorization forwarding is broken.
    });
    </script>
    <?php
}

function vormbyte_render_enable_prompt(?WP_Error $dependency_error): void
{
    if (vormbyte_is_enabled() || $dependency_error !== null) {
        return;
    }

    ?>
    <p style="color:#666; font-size:14px;">
        <?php esc_html_e('Enable AI Abilities above to connect your AI tool.', domain: 'vormbyte'); ?>
    </p>
    <?php
}

/**
 * Render the connect / setup dashboard page.
 */
// Inherent: a top-level admin page template that emits each section (notices, chooser, connect
// client, disabled-state manage list) inline; the branches map one-to-one onto template regions.
function vormbyte_render_connect_page(): void
{
    if (!vormbyte_current_user_can_manage()) {
        return;
    }

    $mcp_dependency_error = vormbyte_get_mcp_dependency_error();
    $toggle_saved = vormbyte_handle_toggle_enabled();
    $enabled = vormbyte_is_enabled();
    $mcp_ready = $enabled && $mcp_dependency_error === null;

    $password_result = $mcp_ready ? vormbyte_handle_create_password() : null;
    $create_error = is_wp_error($password_result) ? $password_result : null;
    $new_password = is_string($password_result) ? $password_result : null;

    $existing_result = $mcp_ready ? vormbyte_handle_use_existing_password() : null;
    $existing_error = is_wp_error($existing_result) ? $existing_result : null;
    $existing_password = is_string($existing_result) ? $existing_result : null;

    $result_message = match ($_GET['vormbyte_result'] ?? null) {
        'revoked' => __('Application password revoked.', domain: 'vormbyte'),
        default => null,
    };

    $copied_label = esc_js(__('Copied!', domain: 'vormbyte'));

    ?>
    <style>
        .vormbyte-connect-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 6px;
            padding: 20px 24px;
            margin: 0 0 20px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.03);
        }
        .vormbyte-connect-header-end { margin-bottom: 16px; }
        .vormbyte-step-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 0 12px;
            font-size: 18px;
            font-weight: 600;
            color: #1d2327;
        }
        .vormbyte-method-cards { display:flex; gap:12px; margin:0 0 16px; flex-wrap:wrap; }
        .vormbyte-method-card {
            flex:1 1 220px; text-align:left; cursor:pointer; padding:14px 16px;
            border:1px solid #dcdcde; border-radius:6px; background:#fff; display:flex;
            flex-direction:column; gap:6px;
        }
        .vormbyte-method-card[hidden], .vormbyte-method-panel[hidden] { display:none; }
        .vormbyte-method-card:disabled {
            background: #f6f7f7;
            color: #646970;
            cursor: not-allowed;
        }
        .vormbyte-method-card.is-active { border-color:#2271b1; box-shadow:0 0 0 1px #2271b1; }
        .vormbyte-method-title { font-weight:600; display:flex; align-items:center; gap:8px; }
        #vormbyte-no-auth-methods { margin: 0 0 16px; }
        #vormbyte-no-auth-methods p { font-weight: 600; }
        #vormbyte-hosting-oauth-warning { margin: 0 0 16px; }
        .vormbyte-hosting-support-email { margin: 12px 0 4px; }
        .vormbyte-hosting-support-email summary { cursor: pointer; font-weight: 600; }
        .vormbyte-hosting-support-email label { display: block; margin: 12px 0 4px; }
        .vormbyte-hosting-support-email textarea { min-height: 360px; resize: vertical; }
        .vormbyte-recommended-badge {
            font-size:11px; font-weight:600; color:#00693e; background:#edfaef;
            border-radius:10px; padding:1px 8px;
        }
        .vormbyte-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #1d2327;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            flex: 0 0 auto;
        }
        .vormbyte-config-block { position: relative; }
        .vormbyte-config-block pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 16px;
            border-radius: 0 4px 0 0;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }
        .vormbyte-copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 4px 10px;
            font-size: 12px;
            cursor: pointer;
            background: #f6f7f7 !important;
            border-color: #8c8f94 !important;
            color: #1d2327 !important;
        }
        .vormbyte-password-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff8e1;
            border: 1px solid #f0c040;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 12px 0;
        }
        .vormbyte-password-value {
            font-family: monospace;
            font-size: 18px;
            letter-spacing: 1px;
            font-weight: bold;
        }
        .vormbyte-client-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .vormbyte-client-chooser-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 16px 0 8px;
        }
        .vormbyte-client-chooser {
            padding: 10px;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            align-content: flex-start;
        }
        .vormbyte-client-chooser.is-filtering {
            max-height: 300px;
            overflow: auto;
        }
        .vormbyte-ai-client-choice[hidden] { display: none; }
        .vormbyte-client-tab {
            padding: 5px 14px;
            border: 1px solid #c3c4c7;
            background: #f6f7f7;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            color: #1d2327;
        }
        .vormbyte-client-tab.active {
            background: var(--wp-admin-theme-color, #2271b1);
            color: #fff;
            border-color: var(--wp-admin-theme-color, #2271b1);
            font-weight: 600;
        }
        .vormbyte-ai-client-choice.active { font-weight: 400; }
        .vormbyte-top-client-tab {
            padding: 9px 20px;
            font-size: 14px;
        }
        .vormbyte-tab-content { border: 1px solid #c3c4c7; border-radius: 4px; }
        .vormbyte-revoke-btn { color: #d63638 !important; border-color: #d63638 !important; }
        .vormbyte-placeholder { background: #d63638; color: #fff; padding: 1px 4px; border-radius: 3px; }
        .vormbyte-mcp-error-panel {
            background: #fff;
            border-left: 4px solid #d63638;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
            margin: 16px 0 24px;
            padding: 12px 16px;
        }
        .vormbyte-mcp-error-panel h2 {
            color: #1d2327;
            font-size: 16px;
            line-height: 1.4;
            margin: 0 0 8px;
        }
        .vormbyte-mcp-error-panel p {
            font-size: 14px;
            margin: 0;
        }
        .vormbyte-production-warning {
            background: #fff8e1;
            border-left: 4px solid #f0c040;
            padding: 12px 16px;
            margin: 12px 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .vormbyte-production-warning p {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            flex: 1 1 auto;
        }
        .vormbyte-paste-block {
            margin: 12px 0;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            overflow: hidden;
        }
        .vormbyte-paste-header {
            background: #1d2327;
            color: #fff;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .vormbyte-paste-content {
            position: relative;
            background: #f6f7f7;
        }
        .vormbyte-paste-content pre {
            background: transparent;
            color: #1d2327;
            padding: 16px;
            border: none;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
            max-height: 6.5em;
            overflow: hidden;
        }
        .vormbyte-paste-content.is-expanded pre {
            max-height: none;
            overflow: visible;
        }
        .vormbyte-paste-content:not(.is-expanded)::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 32px;
            background: linear-gradient(to bottom, rgba(246, 247, 247, 0), rgba(246, 247, 247, 1));
            pointer-events: none;
        }
        .vormbyte-paste-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 14px 14px;
            background: #fff;
            border-top: 1px solid #c3c4c7;
        }
        .vormbyte-panel-footer {
            margin: 20px 0 0;
            border-top: 1px solid #e0e0e0;
            padding-top: 16px;
        }
    </style>

    <?php vormbyte_render_admin_header(); ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Configuration', domain: 'vormbyte'); ?></h1>
        <a
            class="page-title-action"
            style="margin-left:12px;"
            href="<?php echo esc_url(admin_url('admin.php?page=vormbyte-connections')); ?>"
        ><?php esc_html_e('Manage Connections', domain: 'vormbyte'); ?></a>
        <hr class="wp-header-end vormbyte-connect-header-end" />

        <?php vormbyte_render_mcp_dependency_inline_notice($mcp_dependency_error); ?>

        <?php vormbyte_render_authorization_header_warning(); ?>

        <?php if ($toggle_saved === true): ?>
            <div class="notice notice-success is-dismissible"><p><?php

            esc_html_e('Settings saved.', domain: 'vormbyte');
            ?></p></div>
        <?php endif; ?>

        <?php vormbyte_render_production_warning(); ?>

        <div class="vormbyte-connect-section" id="vormbyte-step1">
            <?php vormbyte_render_enable_toggle(); ?>
        </div>

        <?php vormbyte_render_enable_prompt($mcp_dependency_error); ?>
        <?php if ($mcp_ready): ?>
            <?php if ($create_error !== null): ?>
                <div class="notice notice-error"><p><?php

                echo esc_html($create_error->get_error_message());
                ?></p></div>
            <?php endif; ?>

            <?php if ($result_message !== null): ?>
                <div class="notice notice-success is-dismissible"><p><?php

                echo esc_html($result_message);
                ?></p></div>
            <?php endif; ?>

            <div class="vormbyte-connect-section" id="vormbyte-step2">
                <?php vormbyte_render_ai_client_chooser(); ?>
            </div>

            <div class="vormbyte-connect-section" id="vormbyte-step3">
                <?php vormbyte_render_method_chooser($new_password, $existing_password, $existing_error); ?>
            </div>

            <div class="vormbyte-connect-section" id="vormbyte-step4">
                <?php vormbyte_render_connect_client_section($new_password, $existing_password, $existing_error); ?>
            </div>

            <div class="vormbyte-connect-section">
                <p class="description" style="margin:0;">
                    <?php esc_html_e(
                        'Set up your AI tool above, then use it once. You will see right away if it works.',
                        domain: 'vormbyte',
                    ); ?>
                    <a href="<?php echo
                        esc_url(admin_url('admin.php?page=vormbyte-troubleshoot'))
                    ; ?>"><?php esc_html_e('Not working? Open Troubleshoot', domain: 'vormbyte'); ?></a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // navigator.clipboard exists only in a secure context (HTTPS, or http://localhost). On a local
    // site served over plain HTTP on a non-localhost host it is undefined, so fall back to a hidden
    // textarea + execCommand('copy'), which needs no secure context.
    window.vormbyteClipboardCopy = function (text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(ta);
            ok ? resolve() : reject(new Error('copy command was rejected'));
        });
    };
    function vormbyteCopy(id, btn) {
        var text = document.getElementById(id).textContent;
        window.vormbyteClipboardCopy(text).then(function() {
            var orig = btn.textContent;
            btn.textContent = '<?php echo $copied_label; ?>';
            setTimeout(function() { btn.textContent = orig; }, 1500);
        });
    }
    function vormbyteCopyHostingSupportEmail(btn) {
        var subject = document.getElementById('vormbyte-hosting-support-subject');
        var message = document.getElementById('vormbyte-hosting-support-message');
        if (!subject || !message) { return; }
        window.vormbyteClipboardCopy('Subject: ' + subject.value + '\n\n' + message.value).then(function() {
            var orig = btn.textContent;
            btn.textContent = '<?php echo $copied_label; ?>';
            setTimeout(function() { btn.textContent = orig; }, 1500);
        });
    }
    function vormbyteTogglePasswordName(btn) {
        var field = document.getElementById('vormbyte-password-name-field');
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        if (expanded) {
            field.style.display = 'none';
            field.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        } else {
            field.style.display = 'block';
            field.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            var input = document.getElementById('vormbyte-password-name');
            if (input) { input.focus(); }
        }
    }
    function vormbyteToggleUseExisting(btn) {
        var field = document.getElementById('vormbyte-use-existing-field');
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        if (expanded) {
            field.style.display = 'none';
            field.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        } else {
            field.style.display = 'block';
            field.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            var input = document.getElementById('vormbyte-existing-password');
            if (input) { input.focus(); }
        }
    }
    </script>
    <?php
}
