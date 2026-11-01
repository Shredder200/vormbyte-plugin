<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

require_once __DIR__ . '/../../includes/troubleshoot/checks.php';

use function Vormbyte\Troubleshoot\Checks\build_support_report;

final class SupportReportTest extends TestCase
{
    /** @return array{site_url: string, vormbyte_version: string, wp_version: string, php_version: string, method: string, hosting: string} */
    private function meta(string $hosting = ''): array
    {
        return [
            'site_url' => 'https://example.com',
            'vormbyte_version' => '1.10.0',
            'wp_version' => '6.9',
            'php_version' => '8.3.0',
            'method' => 'oauth',
            'hosting' => $hosting,
        ];
    }

    public function testIncludesContextLayersAndEveryCheck(): void
    {
        $checks = [
            ['id' => 'a', 'status' => 'ok', 'label' => 'Fine', 'message' => 'all good', 'remedy' => '', 'action' => '', 'copy' => ''],
            ['id' => 'b', 'status' => 'fail', 'label' => 'Discovery', 'message' => '404 on well-known', 'remedy' => '', 'action' => '', 'copy' => ''],
            ['id' => 'c', 'status' => 'warning', 'label' => 'Bot filter', 'message' => 'UA blocked', 'remedy' => '', 'action' => '', 'copy' => ''],
        ];

        $report = build_support_report($this->meta('Kinsta'), ['Cloudflare', 'Wordfence'], $checks);

        self::assertStringContainsString('https://example.com', $report);
        self::assertStringContainsString('Hosting detected: Kinsta', $report);
        self::assertStringContainsString('Vormbyte: 1.10.0', $report);
        self::assertStringContainsString('Cloudflare, Wordfence', $report);
        self::assertStringContainsString('[FAIL] Discovery: 404 on well-known', $report);
        self::assertStringContainsString('[WARNING] Bot filter: UA blocked', $report);
        // Passing checks are included too, so the report shows the full battery that ran.
        self::assertStringContainsString('[OK] Fine: all good', $report);
    }

    public function testAllPassingListsEveryCheckAndNoLayers(): void
    {
        $checks = [
            ['id' => 'a', 'status' => 'ok', 'label' => 'Fine', 'message' => 'all good', 'remedy' => '', 'action' => '', 'copy' => ''],
        ];

        $report = build_support_report($this->meta(), [], $checks);

        self::assertStringNotContainsString('Hosting detected:', $report);
        self::assertStringContainsString('Security/edge detected: none', $report);
        self::assertStringContainsString('[OK] Fine: all good', $report);
    }
}
