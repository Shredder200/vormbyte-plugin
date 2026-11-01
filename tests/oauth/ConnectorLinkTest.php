<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/connect-methods.php';

final class ConnectorLinkTest extends TestCase
{
    public function testInstallLinkEncodesParams(): void
    {
        $link = vormbyte_build_connector_install_link(
            mcp_url: 'https://example.com/wp-json/mcp/vormbyte',
            connector_name: 'Vormbyte - My Site',
        );
        self::assertSame(
            'https://claude.ai/customize/connectors?modal=add-custom-connector'
            . '&connectorName=Vormbyte%20-%20My%20Site'
            . '&connectorUrl=https%3A%2F%2Fexample.com%2Fwp-json%2Fmcp%2Fvormbyte',
            $link,
        );
    }

    public function testBridgeServerCarriesTlsBypassAndUrl(): void
    {
        $server = vormbyte_oauth_bridge_server(
            mcp_url: 'https://test.local:8890/wp-json/mcp/vormbyte',
            env: ['NODE_TLS_REJECT_UNAUTHORIZED' => '0'],
        );
        self::assertSame('npx', $server['command']);
        self::assertSame(['-y', 'mcp-remote', 'https://test.local:8890/wp-json/mcp/vormbyte'], $server['args']);
        self::assertSame('0', $server['env']['NODE_TLS_REJECT_UNAUTHORIZED']);
    }

    public function testBridgeServerWithoutEnvOmitsEnvKey(): void
    {
        $server = vormbyte_oauth_bridge_server(
            mcp_url: 'https://example.com/wp-json/mcp/vormbyte',
            env: [],
        );
        self::assertArrayNotHasKey('env', $server);
    }

    public function testDisplayNameWithAndWithoutSiteName(): void
    {
        self::assertSame('Vormbyte - My Blog', vormbyte_build_connector_display_name('My Blog'));
        self::assertSame('Vormbyte - A & B', vormbyte_build_connector_display_name('A &amp; B'));
        self::assertSame('Vormbyte', vormbyte_build_connector_display_name('   '));
        self::assertSame('Vormbyte', vormbyte_build_connector_display_name('&nbsp;'));
        self::assertSame('Vormbyte - Demo', vormbyte_build_connector_display_name('Vormbyte Demo'));
        self::assertSame('Vormbyte', vormbyte_build_connector_display_name('Vormbyte'));
    }

    public function testInvalidUtf8SiteNameFallsBackToByteSafeTrim(): void
    {
        $site_name = "Caf\xE9";

        self::assertNotSame('', vormbyte_plain_site_name($site_name));
        self::assertSame(trim($site_name), vormbyte_plain_site_name($site_name));
        self::assertNotSame('Vormbyte', vormbyte_build_connector_display_name($site_name));
        self::assertSame("ok\xC3", vormbyte_plain_site_name(" ok\xC3 "));
    }
}
