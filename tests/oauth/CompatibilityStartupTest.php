<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}
if (!function_exists('add_action')) {
    function add_action(
        string $hook_name,
        callable|string $callback,
        int $priority = 10,
        int $accepted_args = 1,
    ): bool {
        $GLOBALS['vormbyte_test_actions'][] = [$hook_name, $callback, $priority, $accepted_args];
        return true;
    }
}
if (!function_exists('add_filter')) {
    function add_filter(
        string $hook_name,
        callable|string $callback,
        int $priority = 10,
        int $accepted_args = 1,
    ): bool {
        $GLOBALS['vormbyte_test_filters'][] = [$hook_name, $callback, $priority, $accepted_args];
        return true;
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return $show === 'version' ? (string) ($GLOBALS['wp_version'] ?? '') : '';
    }
}
if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return rtrim((string) ($GLOBALS['vormbyte_test_home'] ?? 'https://example.test'), characters: '/') . $path;
    }
}
if (!function_exists('rest_url')) {
    function rest_url(string $path = ''): string
    {
        return home_url('/wp-json/' . ltrim($path, characters: '/'));
    }
}
if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return home_url('/wp-admin/' . ltrim($path, characters: '/'));
    }
}
if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }
}
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return $text;
    }
}
if (!function_exists('vormbyte_current_user_can_manage')) {
    function vormbyte_current_user_can_manage(): bool
    {
        $GLOBALS['vormbyte_test_current_user_can_manage_calls'] =
            ($GLOBALS['vormbyte_test_current_user_can_manage_calls'] ?? 0) + 1;
        return (bool) ($GLOBALS['vormbyte_test_current_user_can_manage'] ?? false);
    }
}
if (!function_exists('vormbyte_admin_ui_visible')) {
    function vormbyte_admin_ui_visible(): bool
    {
        return true;
    }
}
if (!function_exists('wp_admin_notice')) {
    /** @param array<string, mixed> $args */
    function wp_admin_notice(string $message, array $args = []): void
    {
        $GLOBALS['vormbyte_test_notices'][] = [$message, $args];
    }
}

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/compatibility.php';
require_once __DIR__ . '/../../includes/oauth/bootstrap.php';
require_once __DIR__ . '/../../includes/oauth/endpoints/discovery.php';
require_once __DIR__ . '/../../includes/abilities/bootstrap.php';

final class CompatibilityStartupTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wp_version'] = '6.9.2';
        $GLOBALS['vormbyte_test_home'] = 'https://example.test';
        $GLOBALS['vormbyte_test_actions'] = [];
        $GLOBALS['vormbyte_test_filters'] = [];
        $GLOBALS['vormbyte_test_notices'] = [];
        $GLOBALS['vormbyte_test_current_user_can_manage'] = true;
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['wp_version'],
            $GLOBALS['vormbyte_test_home'],
            $GLOBALS['vormbyte_test_actions'],
            $GLOBALS['vormbyte_test_filters'],
            $GLOBALS['vormbyte_test_notices'],
            $GLOBALS['vormbyte_test_current_user_can_manage'],
        );
    }

    public function testPluginHeaderAndInternalVersionsAgree(): void
    {
        $plugin = file_get_contents(__DIR__ . '/../../vormbyte.php');
        self::assertIsString($plugin);
        self::assertMatchesRegularExpression('/^ \* Version: ' . preg_quote(VORMBYTE_VERSION, '/') . '$/m', $plugin);
        self::assertMatchesRegularExpression(
            '/^ \* Requires at least: ' . preg_quote(VORMBYTE_MINIMUM_WORDPRESS_VERSION, '/') . '$/m',
            $plugin,
        );
    }

    public function testAuthorizationServerMetadataSatisfiesOpenIdProviderDiscovery(): void
    {
        // The document also answers openid-configuration; the MCP TypeScript SDK rejects it there
        // unless every field OpenID Provider metadata requires is present.
        $metadata = \Vormbyte\OAuth\Endpoints\Discovery\authorization_server_document();

        foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $field) {
            self::assertIsString($metadata[$field] ?? null, $field);
        }
        self::assertStringEndsWith('/vormbyte/v1/oauth/jwks', $metadata['jwks_uri']);
        self::assertSame(['code'], $metadata['response_types_supported']);
        self::assertSame(['public'], $metadata['subject_types_supported']);
        self::assertSame(['RS256'], $metadata['id_token_signing_alg_values_supported']);
        self::assertNotContains('openid', $metadata['scopes_supported']);
    }

    public function testCompatibilityMetadataHasTheFrozenShapeAndCurrentValues(): void
    {
        $metadata = \Vormbyte\OAuth\Endpoints\Discovery\protected_resource_document();

        self::assertSame('https://example.test/wp-json/mcp/vormbyte-oauth', $metadata['resource']);
        self::assertSame(['https://example.test'], $metadata['authorization_servers']);
        self::assertSame(['header'], $metadata['bearer_methods_supported']);
        self::assertSame(['full_access'], $metadata['scopes_supported']);
        self::assertSame(
            ['full_access'],
            \Vormbyte\OAuth\Endpoints\Discovery\authorization_server_document()['scopes_supported'],
        );
        self::assertSame(
            [
                // Derived, not pinned: the shape is what is frozen here, and
                // testPluginHeaderAndInternalVersionsAgree covers the value
                'plugin_version' => VORMBYTE_VERSION,
                'rest_api_version' => 1,
                'wordpress_version' => '6.9.2',
                'minimum_wordpress_version' => '6.9',
                'features' => [
                    'abilities_bearer_auth' => true,
                    'agent_context' => true,
                    'rest_skills' => true,
                    'generalized_execution_shim' => true,
                ],
            ],
            $metadata['vormbyte'],
        );
    }

    #[DataProvider('wellKnownPaths')]
    public function testWellKnownDiscoveryUsesTheWordPressInstallationBase(
        string $home,
        string $role,
        string $expected,
    ): void {
        $GLOBALS['vormbyte_test_home'] = $home;

        $paths = \Vormbyte\OAuth\Endpoints\Discovery\discovery_paths(
            home_url(),
            \Vormbyte\OAuth\resource_identifier(),
        );

        self::assertContains($expected, $paths[$role]);
    }

    /** @return array<string, array{string, string, string}> */
    public static function wellKnownPaths(): array
    {
        return [
            'root protected resource' => [
                'https://example.test',
                'protected_resource',
                '/.well-known/oauth-protected-resource',
            ],
            'subdirectory protected resource' => [
                'https://example.test/blog',
                'protected_resource',
                '/blog/.well-known/oauth-protected-resource',
            ],
            'subdirectory authorization server' => [
                'https://example.test/wordpress',
                'authorization_server',
                '/wordpress/.well-known/oauth-authorization-server',
            ],
        ];
    }

    public function testSupportedWordPressRegistersRestAndAbilitiesWithoutInitializingTheAdapter(): void
    {
        self::assertTrue(vormbyte_boot_ability_rest_surface());
        self::assertTrue(vormbyte_register_ability_hooks());
        self::assertTrue(vormbyte_register_ability_policy_hook());

        self::assertContains(
            ['rest_api_init', 'vormbyte_register_ability_run_rest_shim', 10, 1],
            $GLOBALS['vormbyte_test_actions'],
        );
        self::assertContains(
            ['rest_request_before_callbacks', 'vormbyte_prevent_restricted_rest_ability_run', 10, 3],
            $GLOBALS['vormbyte_test_filters'],
        );
        self::assertContains(
            ['rest_request_after_callbacks', 'vormbyte_filter_rest_ability_metadata', 10, 3],
            $GLOBALS['vormbyte_test_filters'],
        );
        self::assertContains(
            ['wp_abilities_api_categories_init', 'vormbyte_register_ability_categories', 20, 1],
            $GLOBALS['vormbyte_test_actions'],
        );
        self::assertContains(
            ['wp_abilities_api_init', 'vormbyte_register_builtin_abilities', 20, 1],
            $GLOBALS['vormbyte_test_actions'],
        );
        self::assertContains(
            ['wp_abilities_api_init', 'vormbyte_attach_ability_policy_sweep', PHP_INT_MAX - 1, 1],
            $GLOBALS['vormbyte_test_actions'],
        );
    }

    public function testWordPress68WarnsAndRegistersNoAbilitySurface(): void
    {
        $GLOBALS['wp_version'] = '6.8.3';

        vormbyte_register_wordpress_compatibility_notice();
        self::assertFalse(vormbyte_boot_ability_rest_surface());
        self::assertFalse(vormbyte_register_ability_hooks());
        self::assertFalse(vormbyte_register_ability_policy_hook());

        $hooks = array_column($GLOBALS['vormbyte_test_actions'], 0);
        self::assertSame(['admin_notices', 'network_admin_notices'], $hooks);
        self::assertSame([], $GLOBALS['vormbyte_test_filters']);

        vormbyte_render_wordpress_compatibility_notice();
        self::assertCount(1, $GLOBALS['vormbyte_test_notices']);
        self::assertStringContainsString('WordPress 6.9 or newer', $GLOBALS['vormbyte_test_notices'][0][0]);
        self::assertStringContainsString('WordPress 6.8.3 is installed', $GLOBALS['vormbyte_test_notices'][0][0]);
    }
}
