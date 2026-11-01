<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GhostModeSurfaceGatesTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function surfaces(): iterable
    {
        yield 'MCP dependency notice' => ['vormbyte.php', 'function vormbyte_render_mcp_dependency_notice('];
        yield 'Admin bar toggle' => ['vormbyte.php', 'function vormbyte_register_admin_bar_toggle('];
        yield 'Admin bar assets' => ['vormbyte.php', 'function vormbyte_render_admin_bar_toggle_assets('];
        yield 'Domain mismatch notice' => ['vormbyte.php', "if (!\$is_enabled && vormbyte_is_domain_mismatch()) {\n    add_action('admin_notices', static function () {"];
        yield 'WordPress compatibility notice' => ['includes/compatibility.php', 'function vormbyte_render_wordpress_compatibility_notice('];
        yield 'Persistent notice renderer' => ['includes/admin-notices.php', 'function vormbyte_render_persistent_admin_notice('];
        yield 'Sandbox safe mode notice' => ['includes/sandbox-loader.php', "add_action('admin_notices', static function () use (\$crashed_file) {"];
        yield 'Design notices' => ['includes/design/notices.php', 'function render('];
        yield 'Skills notices' => ['includes/skills/notices.php', 'function render('];
        yield 'Troubleshoot notice' => ['includes/troubleshoot/notice.php', 'function maybe_render('];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('surfaces')]
    public function testSurfaceChecksGateAndVisibilityFirst(string $file, string $anchor): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, levels: 2) . '/' . $file);
        $position = strpos($source, $anchor);
        self::assertNotFalse($position, "Anchor not found in {$file}: {$anchor}");

        $body = substr($source, $position + strlen($anchor), 500);
        self::assertMatchesRegularExpression('/^[^;]*?vormbyte_current_user_can_manage\(\)[^;]*?vormbyte_admin_ui_visible\(\)/s', $body, "{$file}: first statement must check vormbyte_current_user_can_manage() and vormbyte_admin_ui_visible()");
    }

    public function testVisualAuthCheckReportsVisibility(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, levels: 2) . '/vormbyte-visual/includes/Workspace.php');

        self::assertStringContainsString('$can_manage && \vormbyte_admin_ui_visible()', $source);
    }

    public function testUpgraderProcessCompleteSuspendsAndResumesUpdateProjection(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, levels: 2) . '/includes/ghost-mode/bootstrap.php');

        self::assertMatchesRegularExpression(
            "/foreach \\(\\[\s*'load-plugins\\.php',\s*'load-update\\.php',\s*'load-update-core\\.php',\s*'admin_init',\s*'upgrader_process_complete',\s*\\] as \\\$vormbyte_ghost_mode_hook\\)/",
            $source,
        );
    }
}
