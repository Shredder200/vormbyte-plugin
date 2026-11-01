<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed
    {
        return $GLOBALS['nm_transients'][$key] ?? false;
    }
}
if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $ttl = 0): bool
    {
        $GLOBALS['nm_transients'][$key] = $value;
        $GLOBALS['nm_transient_ttls'][$key] = $ttl;
        return true;
    }
}
if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

use PHPUnit\Framework\TestCase;

use function Vormbyte\OAuth\ClientValidation\within_endpoint_rate_limit;

use const Vormbyte\OAuth\ClientValidation\ENDPOINT_RATE_LIMIT_PER_MINUTE;

require_once __DIR__ . '/../../includes/oauth/client-validation.php';

/**
 * Per-IP throttle on the unauthenticated token/revoke endpoints: cap blocks a flood, buckets and
 * IPs are counted independently, and a missing REMOTE_ADDR is not throttled.
 */
final class EndpointRateLimitTest extends TestCase
{
    public function testBlocksAfterCap(): void
    {
        $GLOBALS['nm_transients'] = [];
        for ($i = 0; $i < ENDPOINT_RATE_LIMIT_PER_MINUTE; $i++) {
            self::assertTrue(within_endpoint_rate_limit('token', '203.0.113.5'));
        }
        self::assertFalse(within_endpoint_rate_limit('token', '203.0.113.5'));
    }

    public function testBucketsAndIpsAreIndependent(): void
    {
        $GLOBALS['nm_transients'] = [];
        for ($i = 0; $i < ENDPOINT_RATE_LIMIT_PER_MINUTE; $i++) {
            within_endpoint_rate_limit('token', '203.0.113.5');
        }
        self::assertFalse(within_endpoint_rate_limit('token', '203.0.113.5'));
        self::assertTrue(within_endpoint_rate_limit('revoke', '203.0.113.5'));
        self::assertTrue(within_endpoint_rate_limit('token', '198.51.100.9'));
    }

    public function testEmptyIpIsNotThrottled(): void
    {
        $GLOBALS['nm_transients'] = [];
        for ($i = 0; $i < ENDPOINT_RATE_LIMIT_PER_MINUTE + 5; $i++) {
            self::assertTrue(within_endpoint_rate_limit('token', ''));
        }
    }

    public function testWindowExpiresFromFirstRequestRatherThanLastPoll(): void
    {
        $GLOBALS['nm_transients'] = [];
        $key = 'vormbyte_oauth_rl_token_' . hash('sha256', '203.0.113.5');

        self::assertTrue(within_endpoint_rate_limit('token', '203.0.113.5'));
        self::assertSame(MINUTE_IN_SECONDS, $GLOBALS['nm_transient_ttls'][$key]);

        $GLOBALS['nm_transients'][$key]['started_at'] = time() - 30;
        self::assertTrue(within_endpoint_rate_limit('token', '203.0.113.5'));
        self::assertSame(30, $GLOBALS['nm_transient_ttls'][$key]);

        $GLOBALS['nm_transients'][$key] = ['started_at' => time() - MINUTE_IN_SECONDS, 'count' => ENDPOINT_RATE_LIMIT_PER_MINUTE];
        self::assertTrue(within_endpoint_rate_limit('token', '203.0.113.5'));
        self::assertSame(1, $GLOBALS['nm_transients'][$key]['count']);
        self::assertSame(MINUTE_IN_SECONDS, $GLOBALS['nm_transient_ttls'][$key]);
    }
}
