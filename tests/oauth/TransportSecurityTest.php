<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return $value;
    }
}
if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return (string) ($GLOBALS['vormbyte_test_home'] ?? '');
    }
}
if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }
}
if (!function_exists('wp_get_environment_type')) {
    function wp_get_environment_type(): string
    {
        return (string) ($GLOBALS['vormbyte_test_env'] ?? 'production');
    }
}

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/connect-methods.php';

/**
 * OAuth transport gate: HTTPS anywhere, or plain HTTP only when WordPress reports a local
 * environment — the same policy wp_is_application_passwords_supported() applies. "Looks local"
 * (private IP, *.local/*.test) is not enough over HTTP, because those hosts still share a LAN wire.
 */
final class TransportSecurityTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['vormbyte_test_home'], $GLOBALS['vormbyte_test_env']);
    }

    #[DataProvider('transportCases')]
    public function testOauthTransportAllowed(string $home, string $env, bool $expected): void
    {
        $GLOBALS['vormbyte_test_home'] = $home;
        $GLOBALS['vormbyte_test_env'] = $env;
        self::assertSame($expected, vormbyte_oauth_transport_allowed());
    }

    /** @return array<string, array{0: string, 1: string, 2: bool}> */
    public static function transportCases(): array
    {
        return [
            // HTTPS is allowed regardless of the declared environment.
            'https public, production env' => ['https://example.com', 'production', true],
            'https uppercase scheme, production env' => ['HTTPS://example.com', 'production', true],
            'https local-style host, production env' => ['https://mysite.test', 'production', true],
            // Plain HTTP is refused unless WordPress declares a local environment.
            'http public, production env' => ['http://example.com', 'production', false],
            'http localhost, production env' => ['http://localhost', 'production', false],
            'http loopback ip, production env' => ['http://127.0.0.1:8888', 'production', false],
            'http dot-test, production env' => ['http://mysite.test', 'production', false],
            'http private ip, production env' => ['http://192.168.1.10', 'production', false],
            // A local environment is the explicit developer signal that unlocks plain HTTP.
            'http localhost, local env' => ['http://localhost', 'local', true],
            'http dot-test, local env' => ['http://mysite.test', 'local', true],
            'http private ip, local env' => ['http://192.168.1.10', 'local', true],
        ];
    }
}
