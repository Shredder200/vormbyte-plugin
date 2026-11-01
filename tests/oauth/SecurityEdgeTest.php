<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

require_once __DIR__ . '/../../includes/troubleshoot/checks.php';

use function Vormbyte\Troubleshoot\Checks\detect_security_edge;

final class SecurityEdgeTest extends TestCase
{
    public function testDetectsCloudflareFromCfRayHeader(): void
    {
        self::assertSame(['Cloudflare'], detect_security_edge(['cf-ray' => 'abc-LHR'], []));
    }

    public function testDetectsCloudflareFromServerHeader(): void
    {
        self::assertSame(['Cloudflare'], detect_security_edge(['server' => 'cloudflare'], []));
    }

    public function testDetectsWpEngineFromHeaderPrefix(): void
    {
        self::assertSame(['WP Engine'], detect_security_edge(['x-wpe-request-id' => '123'], []));
    }

    public function testDetectsSucuriAndLiteSpeed(): void
    {
        self::assertSame(
            ['Sucuri', 'LiteSpeed'],
            detect_security_edge(['x-sucuri-id' => '1', 'server' => 'LiteSpeed'], []),
        );
    }

    public function testDetectsActiveSecurityPlugins(): void
    {
        self::assertSame(
            ['Wordfence', 'WP Cerber'],
            detect_security_edge([], ['wordfence/wordfence.php', 'wp-cerber/wp-cerber.php']),
        );
    }

    public function testDeduplicatesSolidSecurityVariantsAndMergesSources(): void
    {
        self::assertSame(
            ['Cloudflare', 'Solid Security'],
            detect_security_edge(
                ['cf-ray' => 'x'],
                ['better-wp-security/better-wp-security.php', 'ithemes-security-pro/ithemes-security-pro.php'],
            ),
        );
    }

    public function testNothingDetectedReturnsEmpty(): void
    {
        self::assertSame([], detect_security_edge(['server' => 'nginx'], ['akismet/akismet.php']));
    }
}
