<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Child-process runner for ReadModeRoutesTest: php read-mode-routes-runner.php <plugin root> <scenario>.
 */

if ($argc !== 3) {
    exit(2);
}

[, $root, $scenario] = $argv;

require __DIR__ . '/read-mode-stubs.php';
require __DIR__ . '/options-wpdb.php';
test_install_pro_stand_in();

class WP_REST_Request implements ArrayAccess
{
    /**
     * @param array<string, string> $headers
     * @param array<string, string> $params
     */
    public function __construct(
        private array $headers = [],
        private array $params = [],
        private string $body = '',
    ) {}

    public function get_header(string $name): ?string
    {
        $GLOBALS['calls'][] = ['get_header', $name];
        return $this->headers[$name] ?? null;
    }

    public function get_url_params(): array
    {
        $GLOBALS['calls'][] = ['get_url_params'];
        return $this->params;
    }

    public function get_body(): string
    {
        return $this->body;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->params[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->params[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->params[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->params[$offset]);
    }
}

class WP_REST_Abilities_V1_Run_Controller
{
    public function execute_ability(): void {}
}

class WP_REST_Server
{
    public const CREATABLE = 'POST';
}

/** No action has run: the MCP adapter has not initialized in this runner. */
function did_action(string $hook): int
{
    return 0;
}

function check_admin_referer(string $action): bool
{
    $GLOBALS['calls'][] = ['check_admin_referer', $action];
    return true;
}

function checked(mixed $checked, mixed $current = true): void
{
    echo $checked === $current ? " checked='checked'" : '';
}

function disabled(mixed $disabled): void
{
    echo $disabled === true ? " disabled='disabled'" : '';
}

function esc_html_e(string $text, string $domain = 'default'): void
{
    echo htmlspecialchars($text);
}

require $root . '/includes/helpers.php';
require $root . '/includes/read-mode/policy.php';
require $root . '/includes/upload-link.php';
require $root . '/includes/admin-access-link.php';
require $root . '/includes/rest-shim.php';
require $root . '/includes/ability-metadata.php';

$GLOBALS['options']['vormbyte_ai_abilities_enabled'] = '1';
$GLOBALS['options']['vormbyte_ai_abilities_domain'] = 'example.test';
if (str_ends_with($scenario, '-on')) {
    test_read_mode(true);
}
if (str_starts_with($scenario, 'links-disabled-')) {
    $GLOBALS['options']['vormbyte_ai_abilities_enabled'] = '0';
}

/** @return array<string, mixed> */
function test_links(): array
{
    $upload = vormbyte_handle_signed_upload(new WP_REST_Request(['x-vormbyte-upload-token' => 'issued-before']));
    $exchange = vormbyte_handle_admin_access_exchange(new WP_REST_Request([
        'x-vormbyte-admin-access-token' => 'issued-before',
        'x-vormbyte-admin-access-nonce' => 'nonce',
    ]));
    $login = vormbyte_handle_admin_access_login(new WP_REST_Request([], ['nonce' => 'issued-before']));

    return [
        'upload' => test_export($upload),
        'exchange' => test_export($exchange),
        'login' => test_export($login),
        'calls' => array_values(array_filter(
            $GLOBALS['calls'],
            static fn(array $call): bool => !in_array($call[0], ['get_option', 'state_query'], strict: true),
        )),
    ];
}

$result = match ($scenario) {
    'links-on', 'links-off', 'links-disabled-on', 'links-disabled-off' => test_links(),
    'shim-on', 'shim-off' => (static function (): array {
        $request = new WP_REST_Request([], ['ability_name' => 'vormbyte/execute-php']);
        test_register('vormbyte/read-query', 'database', ['show_in_rest' => true]);
        $allowed_missing = new WP_REST_Request([], ['ability_name' => 'vormbyte/describe-schema']);

        return [
            'blocked' => test_export(vormbyte_rest_run_ability($request)),
            'allowed_but_missing' => test_export(vormbyte_rest_run_ability($allowed_missing)),
        ];
    })(),
    'core-run-on', 'core-run-off' => (static function (): array {
        $handler = ['callback' => [new WP_REST_Abilities_V1_Run_Controller(), 'execute_ability']];
        $blocked = new WP_REST_Request([], ['name' => 'vormbyte/execute-php']);
        $allowed = new WP_REST_Request([], ['name' => 'vormbyte/read-query']);
        $manager = [
            'blocked' => test_export(vormbyte_prevent_restricted_rest_ability_run(null, $handler, $blocked)),
            'allowed' => test_export(vormbyte_prevent_restricted_rest_ability_run(null, $handler, $allowed)),
        ];
        $GLOBALS['can_manage'] = false;

        return $manager + [
            'non_manager' => test_export(vormbyte_prevent_restricted_rest_ability_run(null, $handler, $blocked)),
        ];
    })(),
    'ability-info-on', 'ability-info-off' => (static function () use ($root): array {
        require $root . '/includes/abilities/discover-abilities.php';
        return [
            'blocked' => test_export(vormbyte_get_ability_info_read_mode_error('vormbyte/execute-php')),
            'allowed' => test_export(vormbyte_get_ability_info_read_mode_error('vormbyte/read-query')),
        ];
    })(),
    'visual-on', 'visual-off' => (static function () use ($root): array {
        require $root . '/vormbyte-visual/includes/BackendTools.php';
        return ['blocked' => test_export(\Vormbyte\Visual\BackendTools\get_backend_tool('vormbyte/execute-php'))];
    })(),
    'late-ability-on', 'late-ability-off' => (static function () use ($root): array {
        require $root . '/includes/chat.php';
        require $root . '/vormbyte-visual/includes/BackendTools.php';
        // Admin-page helpers Chat's tool discovery uses.
        function vormbyte_ability_is_exposed(array $meta): bool
        {
            return true;
        }
        function vormbyte_ability_is_hub_hidden(string $ability_name): bool
        {
            return false;
        }
        function wp_get_ability_category(string $slug): ?object
        {
            return null;
        }
        require $root . '/includes/abilities/discover-abilities.php';

        // A write Ability that is still registered, as if a provider had registered it after the sweep.
        $GLOBALS['late_executions'] = 0;
        $register_late = static function (): void {
            wp_register_ability('acme/late-write', [
                'category' => 'acme',
                'execute_callback' => static function (): string {
                    $GLOBALS['late_executions']++;
                    return 'written';
                },
                'meta' => ['show_in_rest' => true, 'mcp' => ['public' => true, 'type' => 'tool']],
            ]);
        };

        // Each surface is entered with the Ability registered again, to show that it sweeps on entry.
        $swept = [];
        foreach ([
            'rest_shim' => static fn(): mixed => vormbyte_rest_run_ability(new WP_REST_Request([], ['ability_name' => 'vormbyte/read-query'], '{"input":{}}')),
            'chat_discover' => static fn(): mixed => vormbyte_chat_discover_tools(),
            'chat_prepare' => static fn(): mixed => vormbyte_chat_prepare_tool_execution(['tool_calls' => []], 0, false),
            'visual_discover' => static fn(): mixed => \Vormbyte\Visual\BackendTools\discover_data([]),
            'visual_call' => static fn(): mixed => \Vormbyte\Visual\BackendTools\call_data(['name' => '']),
        ] as $surface => $enter) {
            $register_late();
            $enter();
            $swept[$surface] = !wp_has_ability('acme/late-write');
        }
        $register_late();

        $shim = vormbyte_rest_run_ability(new WP_REST_Request([], ['ability_name' => 'acme/late-write'], '{"input":{}}'));
        $chat = vormbyte_chat_prepare_tool_execution(
            ['tool_calls' => [[
                'ability' => 'acme/late-write',
                'status' => 'approved',
                'risk' => ['requires_approval' => false],
            ]]],
            0,
            false,
        );
        $visual = \Vormbyte\Visual\BackendTools\call_data(['name' => 'acme/late-write', 'args' => []]);

        $component = static fn(string $ability_name): object => new class($ability_name) {
            public function __construct(
                private string $ability_name,
            ) {}

            public function get_observability_context(): array
            {
                return ['ability_name' => $this->ability_name];
            }
        };
        $mcp = [];
        foreach (['tool', 'prompt', 'resource'] as $type) {
            $mcp[$type] = test_export(vormbyte_read_mode_refuse_mcp_component(['a' => 1], $type, $component('acme/late-write')));
        }
        // An object that only claims an allowlisted name in its context, and one with no Ability at all,
        // have no verified binding to Vormbyte's implementation.
        $mcp['claims_allowed_name'] = test_export(vormbyte_read_mode_refuse_mcp_component(['a' => 1], 'tool', $component('vormbyte/read-query')));
        $mcp['not_ability_backed'] = test_export(vormbyte_read_mode_refuse_mcp_component(['a' => 1], 'tool', new stdClass()));

        $GLOBALS['can_manage'] = false;
        $discover = $GLOBALS['abilities']['vormbyte-mcp-adapter/discover-abilities']->get_args()['execute_callback'];
        $listed = array_column($discover()['abilities'], 'name');

        return [
            'shim' => test_export($shim),
            'chat' => $chat instanceof WP_Error ? test_export($chat) : ['ability_name' => $chat['ability_name']],
            'visual' => test_export($visual),
            'mcp' => $mcp,
            'discover_lists_late' => in_array('acme/late-write', $listed, strict: true),
            'executions' => $GLOBALS['late_executions'],
            'swept_on_entry' => $swept,
        ];
    })(),
    'visual-localize-on', 'visual-localize-off' => (static function () use ($root): array {
        define('VORMBYTE_VERSION', '0.0.0-test');
        define('VORMBYTE_PLUGIN_URL', 'https://example.test/wp-content/plugins/vormbyte/');
        $GLOBALS['localized'] = [];
        // The script-loader functions the editor bundle and the workspace page call.
        function wp_enqueue_script(string $handle, string $src = '', array $deps = [], mixed $ver = false, mixed $args = []): void {}
        function wp_localize_script(string $handle, string $object_name, array $l10n): bool
        {
            $GLOBALS['localized'][$object_name] = $l10n;
            return true;
        }
        function esc_url_raw(string $url): string
        {
            return $url;
        }
        function rest_url(string $path = ''): string
        {
            return 'https://example.test/wp-json/' . $path;
        }
        function wp_create_nonce(string $action): string
        {
            return 'nonce';
        }
        function wp_nonce_url(string $url, string $action): string
        {
            return $url;
        }
        function wp_login_url(string $redirect = ''): string
        {
            return 'https://example.test/wp-login.php';
        }
        function get_bloginfo(string $show = ''): string
        {
            return 'Example';
        }
        require $root . '/includes/connect-page.php';
        require $root . '/vormbyte-visual/bootstrap.php';

        foreach ($GLOBALS['hooks'] as [$hook, $callback]) {
            if ($hook === 'enqueue_block_editor_assets') {
                $callback();
            }
        }
        $workspace = (new ReflectionClass(\VormbyteVisual\Workspace::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod($workspace, 'enqueue_workspace_assets'))->invoke($workspace);

        $editor = $GLOBALS['localized']['vormbyteVisualData'];
        $page = $GLOBALS['localized']['vormbyteVisualWorkspaceData'];

        return [
            'editor_read_mode' => $editor['readMode'],
            'workspace_read_mode' => $page['readMode'],
            'workspace_message' => $page['readModeMessage'],
        ];
    })(),
    'visual-backend-on' => (static function () use ($root): array {
        require $root . '/vormbyte-visual/includes/BackendTools.php';
        test_read_mode(true);
        return [
            'discover' => test_export(\Vormbyte\Visual\BackendTools\discover_data([])),
            'call' => test_export(\Vormbyte\Visual\BackendTools\call_data(['name' => 'vormbyte/read-file'])),
        ];
    })(),
    'visual-page-on', 'visual-session-on', 'visual-session-off' => (static function () use ($root, $scenario): array {
        define('VORMBYTE_VERSION', '0.0.0-test');
        define('VORMBYTE_PLUGIN_URL', 'https://example.test/wp-content/plugins/vormbyte/');
        $GLOBALS['sent'] = null;
        foreach (
            [
                'wp_enqueue_style' => static function (): void {},
                'wp_print_styles' => static function (): void {},
                'language_attributes' => static function (): void {},
                'bloginfo' => static function (string $show = ''): void {
                    echo 'UTF-8';
                },
                'esc_url' => static fn(string $url): string => $url,
                'wp_login_url' => static fn(string $redirect = ''): string => 'https://example.test/wp-login.php',
                'vormbyte_current_user_can_manage' => static fn(): bool => true,
                'vormbyte_admin_ui_visible' => static fn(): bool => true,
            ] as $name => $stub
        ) {
            if (!function_exists($name)) {
                // Declared at run time, only when the stubs above did not define them already.
                eval('function ' . $name . '(...$args) { return $GLOBALS["stubs"]["' . $name . '"](...$args); }');
            }
            $GLOBALS['stubs'][$name] = $stub;
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success(mixed $data = null): void
            {
                $GLOBALS['sent'] = $data;
            }
        }
        require $root . '/vormbyte-visual/includes/Workspace.php';
        test_read_mode($scenario !== 'visual-session-off');

        $workspace = (new ReflectionClass(\VormbyteVisual\Workspace::class))->newInstanceWithoutConstructor();
        if ($scenario === 'visual-page-on') {
            ob_start();
            $workspace->render_read_mode_page();
            return ['html' => (string) ob_get_clean()];
        }

        $workspace->ajax_auth_check();
        return ['session' => $GLOBALS['sent']];
    })(),
    default => ['unknown_scenario' => $scenario],
};

echo json_encode($result, JSON_THROW_ON_ERROR);
