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
if (!function_exists('is_404')) {
    function is_404(): bool
    {
        return (bool) ($GLOBALS['vormbyte_test_is_404'] ?? false);
    }
}

use Vormbyte\OAuth\Endpoints\LegacyFallback;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/oauth/endpoints/legacy-fallback.php';

final class LegacyFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['vormbyte_test_actions'] = [];
        $GLOBALS['vormbyte_test_is_404'] = false;
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['vormbyte_test_actions'],
            $GLOBALS['vormbyte_test_is_404'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
        );
    }

    public function testRegistersBeforeCanonicalRedirects(): void
    {
        LegacyFallback\register();

        self::assertSame([
            ['template_redirect', LegacyFallback::class . '\\maybe_serve', 0, 1],
        ], $GLOBALS['vormbyte_test_actions']);
    }

    public function testLeavesAnExistingWordPressRouteAlone(): void
    {
        $_SERVER['REQUEST_URI'] = '/authorize?response_type=code';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        LegacyFallback\maybe_serve();

        self::assertFalse($GLOBALS['vormbyte_test_is_404']);
    }

    #[DataProvider('recognizedRequests')]
    public function testRecognizesLegacyFallbackRequests(string $uri, string $method, string $expected): void
    {
        self::assertSame($expected, LegacyFallback\endpoint_for_request($uri, $method));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function recognizedRequests(): iterable
    {
        yield 'authorization GET' => ['/authorize?response_type=code', 'GET', 'authorize'];
        yield 'registration POST' => ['/register', 'post', 'register'];
        yield 'token POST' => ['/token?ignored=1', 'POST', 'token'];
    }

    #[DataProvider('ignoredRequests')]
    public function testIgnoresNonFallbackRequests(string $uri, string $method): void
    {
        self::assertNull(LegacyFallback\endpoint_for_request($uri, $method));
    }

    /** @return iterable<string, array{string, string}> */
    public static function ignoredRequests(): iterable
    {
        yield 'authorization POST' => ['/authorize', 'POST'];
        yield 'registration GET' => ['/register', 'GET'];
        yield 'token GET' => ['/token', 'GET'];
        yield 'WordPress index prefix' => ['/index.php/authorize', 'GET'];
        yield 'subdirectory lookalike' => ['/blog/authorize', 'GET'];
        yield 'unrelated path' => ['/something-else', 'GET'];
        yield 'malformed URI' => ['https://[invalid', 'GET'];
    }
}
