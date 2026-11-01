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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/connect-methods.php';

final class HostReachabilityTest extends TestCase
{
    #[DataProvider('localHosts')]
    public function testLocalOnlyHost(string $host): void
    {
        self::assertTrue(vormbyte_host_is_local_only($host));
    }

    #[DataProvider('publicHosts')]
    public function testPublicHost(string $host): void
    {
        self::assertFalse(vormbyte_host_is_local_only($host));
    }

    /** @return array<string, array{0: string}> */
    public static function localHosts(): array
    {
        return [
            'localhost' => ['localhost'],
            'single label' => ['wordpress'],
            'loopback v4' => ['127.0.0.1'],
            'loopback v6' => ['::1'],
            'rfc1918 10' => ['10.0.0.5'],
            'rfc1918 192' => ['192.168.1.10'],
            'dot local' => ['site.local'],
            'dot test' => ['foo.test'],
            'ddev' => ['x.ddev.site'],
            'lando' => ['x.lndo.site'],
            'bracketed loopback v6' => ['[::1]'],
        ];
    }

    /** @return array<string, array{0: string}> */
    public static function publicHosts(): array
    {
        return [
            'bracketed public v6' => ['[2001:4860:4860::8888]'],
            'public domain' => ['example.com'],
            'public staging subdomain' => ['staging.example.com'],
            'public instawp' => ['arestes.instawp.co'],
            'public ip' => ['93.184.216.34'],
        ];
    }
}
