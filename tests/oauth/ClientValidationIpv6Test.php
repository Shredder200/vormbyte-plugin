<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/oauth/client-validation.php';

final class ClientValidationIpv6Test extends TestCase
{
    public function testBracketedIpv6LoopbackDeniedInProd(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://[::1]/cb', dev_mode: false));
    }

    public function testBracketedIpv6LoopbackAllowedInDev(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://[::1]/cb', dev_mode: true));
    }

    public function testBracketedIpv6PrivateDenied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://[fd00::1]/cb'));
    }

    public function testBracketedIpv6LinkLocalDenied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://[fe80::1]/cb'));
    }

    public function testIpv4MappedIpv6Denied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://[::ffff:127.0.0.1]/cb'));
    }

    public function testBracketedPublicIpv6Allowed(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('https://[2001:4860:4860::8888]/cb'));
    }

    public function testHttpBracketedLoopbackAllowed(): void
    {
        self::assertTrue(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('http://[::1]/cb'));
    }

    public function testHttpBracketedNonLoopbackDenied(): void
    {
        self::assertFalse(\Vormbyte\OAuth\ClientValidation\is_allowed_redirect_uri('http://[2001:4860:4860::8888]/cb'));
    }
}
