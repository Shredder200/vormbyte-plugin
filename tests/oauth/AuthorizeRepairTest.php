<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}
if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return (bool) ($GLOBALS['vormbyte_test_is_admin'] ?? true);
    }
}

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/oauth/endpoints/authorize.php';

/**
 * Some OAuth clients append their query with "?" instead of "&", folding the OAuth parameters onto
 * the "?page=<slug>" of our admin-hosted authorization endpoint. repair_folded_request() splits them
 * back out on plugins_loaded so admin.php routes to the real page instead of denying the request.
 */
final class AuthorizeRepairTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $_GET['page'],
            $_GET['response_type'],
            $_GET['client_id'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['QUERY_STRING'],
            $GLOBALS['vormbyte_test_is_admin'],
        );
    }

    public function testRepairsFoldedAuthorizationQuery(): void
    {
        // PHP parses "page=vormbyte-oauth-authorize?response_type=code&client_id=abc" so the stray
        // "?" folds response_type onto the page value; client_id already parsed out on its own.
        $_GET['page'] = 'vormbyte-oauth-authorize?response_type=code';
        $_GET['client_id'] = 'abc';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=vormbyte-oauth-authorize?response_type=code&client_id=abc';
        $_SERVER['QUERY_STRING'] = 'page=vormbyte-oauth-authorize?response_type=code&client_id=abc';

        \Vormbyte\OAuth\Endpoints\Authorize\repair_folded_request();

        self::assertSame('vormbyte-oauth-authorize', $_GET['page']);
        self::assertSame('code', $_GET['response_type']);
        self::assertSame('abc', $_GET['client_id']);
        self::assertStringNotContainsString('authorize?response_type', (string) $_SERVER['REQUEST_URI']);
        self::assertStringContainsString('authorize&response_type', (string) $_SERVER['REQUEST_URI']);
    }

    public function testLeavesACleanRequestUntouched(): void
    {
        $_GET['page'] = 'vormbyte-oauth-authorize';
        \Vormbyte\OAuth\Endpoints\Authorize\repair_folded_request();
        self::assertSame('vormbyte-oauth-authorize', $_GET['page']);
        self::assertArrayNotHasKey('response_type', $_GET);
    }

    public function testIgnoresRequestsOutsideTheAdmin(): void
    {
        $GLOBALS['vormbyte_test_is_admin'] = false;
        $_GET['page'] = 'vormbyte-oauth-authorize?response_type=code';
        \Vormbyte\OAuth\Endpoints\Authorize\repair_folded_request();
        self::assertSame('vormbyte-oauth-authorize?response_type=code', $_GET['page']);
    }
}
