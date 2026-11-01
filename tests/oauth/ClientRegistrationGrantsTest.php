<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}
if (!function_exists('add_action')) {
    function add_action(
        string $hook_name,
        callable|string $callback,
        int $priority = 10,
        int $accepted_args = 1,
    ): bool {
        $GLOBALS['vormbyte_test_actions'][] = [$hook_name, $callback, $priority, $accepted_args];
        return true;
    }
}
if (!function_exists('rest_url')) {
    function rest_url(string $path = ''): string
    {
        return 'https://example.test/wp-json/' . ltrim($path, characters: '/');
    }
}

use PHPUnit\Framework\TestCase;

use function Vormbyte\OAuth\Endpoints\Register\is_device_only_registration;

require_once __DIR__ . '/../../includes/oauth/bootstrap.php';
require_once __DIR__ . '/../../includes/oauth/endpoints/register.php';

/**
 * Which registration requests are device-authorization clients.
 *
 * A device client registers no redirect URI, and the authorize endpoint reads that empty list as
 * "approve this under Authorize Device instead". Misclassifying a client here therefore does not
 * degrade its registration, it locks the client out of the authorization code flow entirely.
 */
final class ClientRegistrationGrantsTest extends TestCase
{
    private const DEVICE = \Vormbyte\OAuth\DEVICE_CODE_GRANT_TYPE;

    public function testDeviceGrantAloneIsADeviceClient(): void
    {
        self::assertTrue(is_device_only_registration([self::DEVICE], null));
        self::assertTrue(is_device_only_registration([self::DEVICE, 'refresh_token'], []));
    }

    /**
     * The regression behind issue #85: VS Code advertises the device grant alongside the
     * authorization code grant and supplies the loopback redirect URI it binds. It must register as
     * an authorization code client with that URI kept, not as device-only with the URI discarded.
     */
    public function testDeviceGrantWithAuthorizationCodeAndRedirectUriIsNotADeviceClient(): void
    {
        self::assertFalse(is_device_only_registration(
            ['authorization_code', 'refresh_token', self::DEVICE],
            ['http://127.0.0.1:33418/'],
        ));
    }

    /**
     * A client that asks only for the device grant stays a device client even when it sends
     * redirect URIs it will never use, rather than silently losing the one grant it asked for.
     */
    public function testDeviceGrantWithRedirectUrisButNoAuthorizationCodeStaysADeviceClient(): void
    {
        self::assertTrue(is_device_only_registration([self::DEVICE], ['http://127.0.0.1:33418/']));
    }

    /**
     * Asking for the authorization code grant without a redirect URI cannot complete that flow, so
     * the device grant it also asked for remains the only one it can actually use.
     */
    public function testAuthorizationCodeWithoutARedirectUriFallsBackToTheDeviceGrant(): void
    {
        self::assertTrue(is_device_only_registration(['authorization_code', self::DEVICE], []));
    }

    public function testClientsThatNeverAskedForTheDeviceGrantAreNotDeviceClients(): void
    {
        self::assertFalse(is_device_only_registration(['authorization_code'], ['https://example.test/cb']));
        self::assertFalse(is_device_only_registration([], ['https://example.test/cb']));
        self::assertFalse(is_device_only_registration(null, ['https://example.test/cb']));
    }

    /** Grant types arrive straight from a JSON body, so anything at all can turn up here. */
    public function testMalformedGrantTypesAreNotDeviceClients(): void
    {
        self::assertFalse(is_device_only_registration(self::DEVICE, null));
        self::assertFalse(is_device_only_registration(['device_code'], null));
        self::assertFalse(is_device_only_registration(new stdClass(), null));
    }
}
