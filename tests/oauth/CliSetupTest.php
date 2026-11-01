<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/connect-methods.php';

final class CliSetupTest extends TestCase
{
    public function testAgentManifestUsesSafeUniqueInstallerKeys(): void
    {
        $agents = vormbyte_cli_agents();

        self::assertGreaterThan(60, count($agents));
        foreach ($agents as $key => $details) {
            self::assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key);
            self::assertNotSame('', $details['label']);
            self::assertContains($details['scope'], ['global', 'project']);
        }
    }

    public function testMcpClientAliasesResolveToInstallerKeys(): void
    {
        self::assertSame('codex', vormbyte_cli_agent_for_client('codex-cli'));
        self::assertSame('kilo', vormbyte_cli_agent_for_client('kilo-code'));
        self::assertSame('roo', vormbyte_cli_agent_for_client('roo-code'));
        self::assertSame('cursor', vormbyte_cli_agent_for_client('cursor'));
        self::assertNull(vormbyte_cli_agent_for_client('claude-desktop'));
        self::assertNull(vormbyte_cli_agent_for_client('chatgpt'));
    }

    /**
     * No Vormbyte CLI package is published, so the installers must never fetch a package or
     * script by a name this project does not own (anyone could publish under it).
     */
    public function testInstallersNeverFetchUnownedPackages(): void
    {
        foreach (['global', 'project'] as $scope) {
            foreach ([
                vormbyte_cli_unix_install_command('codex', $scope),
                vormbyte_cli_windows_install_command('codex', $scope),
            ] as $command) {
                self::assertStringStartsWith('#', $command);
                self::assertStringNotContainsString('curl', $command);
                self::assertStringNotContainsString('irm ', $command);
                self::assertStringNotContainsString('npm install', $command);
            }
        }
    }

    public function testLoginCommandsCarryOnlyRequestedLocalOverrides(): void
    {
        self::assertSame(
            "vormbyte auth login 'https://example.com/'",
            vormbyte_cli_login_command('https://example.com/'),
        );
        self::assertSame(
            "VORMBYTE_ALLOW_INSECURE_HTTP='1' NODE_TLS_REJECT_UNAUTHORIZED='0' vormbyte auth login 'http://site.test/'",
            vormbyte_cli_login_command('http://site.test/', [
                'VORMBYTE_ALLOW_INSECURE_HTTP' => '1',
                'NODE_TLS_REJECT_UNAUTHORIZED' => '0',
            ]),
        );
        self::assertSame(
            '$env:VORMBYTE_ALLOW_INSECURE_HTTP = \'1\'; $env:NODE_TLS_REJECT_UNAUTHORIZED = \'0\'; '
            . 'vormbyte auth login \'http://site.test/\'',
            vormbyte_cli_windows_login_command('http://site.test/', [
                'VORMBYTE_ALLOW_INSECURE_HTTP' => '1',
                'NODE_TLS_REJECT_UNAUTHORIZED' => '0',
            ]),
        );
    }

    public function testCliTransportAllowsOnlyHttpsOrExplicitLocalDevelopment(): void
    {
        self::assertTrue(vormbyte_cli_transport_allowed('https://example.com/', 'production'));
        self::assertTrue(vormbyte_cli_transport_allowed('http://localhost:8888/', 'production'));
        self::assertTrue(vormbyte_cli_transport_allowed('http://127.0.0.1/', 'production'));
        self::assertTrue(vormbyte_cli_transport_allowed('http://site.test/', 'local'));
        self::assertFalse(vormbyte_cli_transport_allowed('http://example.com/', 'production'));

        self::assertFalse(vormbyte_cli_needs_insecure_http_override('https://site.test/', 'local'));
        self::assertFalse(vormbyte_cli_needs_insecure_http_override('http://localhost/', 'local'));
        self::assertTrue(vormbyte_cli_needs_insecure_http_override('http://site.test/', 'local'));
    }

    public function testShellValuesAreQuoted(): void
    {
        self::assertSame("'site'\\''s name'", vormbyte_cli_shell_quote("site's name"));
    }
}
