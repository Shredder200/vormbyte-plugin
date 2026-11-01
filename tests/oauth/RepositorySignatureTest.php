<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Guards against re-introducing method signatures on the OAuth repositories that are
 * incompatible with the installed league/oauth2-server interfaces. Such a mismatch is a
 * PHP E_COMPILE_ERROR raised when the class is loaded, so it cannot be caught in-process:
 * the repositories are loaded in a subprocess and the exit status is asserted instead.
 */
final class RepositorySignatureTest extends TestCase
{
    public function testRepositoriesLoadWithoutFatal(): void
    {
        $root = dirname(__DIR__, levels: 2);
        $script = <<<'PHP'
            define('ABSPATH', __DIR__);
            require $argv[1] . '/vendor/autoload.php';
            foreach (['client', 'access-token', 'auth-code', 'refresh-token', 'scope', 'user'] as $repo) {
                require $argv[1] . '/includes/oauth/repositories/' . $repo . '-repository.php';
            }
            echo 'OK';
            PHP;

        $command = sprintf(
            '%s -r %s %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($script),
            escapeshellarg($root),
        );
        $output = (string) shell_exec($command);

        self::assertSame(
            'OK',
            trim($output),
            'OAuth repositories failed to load — likely a signature incompatible with league/oauth2-server: ' . $output,
        );
    }
}
