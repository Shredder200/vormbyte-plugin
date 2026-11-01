<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/oauth/client-validation.php';

final class ClientValidationTest extends TestCase
{
    public function testHttpsAllowed(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://93.184.216.34/cb'));
    }

    public function testClaudeSchemeAllowed(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('claude://callback'));
    }

    public function testCursorSchemeAllowed(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('cursor://callback'));
    }

    public function testHttpDenied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('http://example.com/cb'));
    }

    public function testUnknownSchemeDenied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('evil://x'));
    }

    public function testRfc1918Denied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://10.0.0.5/cb'));
    }

    public function testClass192Denied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://192.168.1.1/cb'));
    }

    public function testLoopbackDeniedInProd(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://127.0.0.1/cb', dev_mode: false));
    }

    public function testLoopbackAllowedInDev(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://127.0.0.1/cb', dev_mode: true));
    }

    public function testFragmentDenied(): void
    {
        // The bare URI is allowed (see testHttpsAllowed); the fragment alone makes it invalid.
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://93.184.216.34/cb#frag'));
    }

    public function testCustomSchemeFragmentDenied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('claude://callback#x'));
    }

    public function testEncodedHashInPathAllowed(): void
    {
        // A percent-encoded '#' is part of the path, not a fragment delimiter, so it stays allowed.
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://93.184.216.34/cb%23x'));
    }
}
