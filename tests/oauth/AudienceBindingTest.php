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

use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Vormbyte\OAuth\Repositories\AccessTokenEntity;
use PHPUnit\Framework\TestCase;

use function Vormbyte\OAuth\resource_identifier;
use function Vormbyte\OAuth\resource_request_allowed;

require_once __DIR__ . '/../../includes/oauth/bootstrap.php';
require_once __DIR__ . '/../../includes/oauth/repositories/access-token-repository.php';

/**
 * RFC 8707 audience binding: an access token is minted with its aud set to this server's MCP
 * resource (not the client id), and a resource request that names a different target is refused.
 */
final class AudienceBindingTest extends TestCase
{
    public function testResourceRequestAllowedOnlyForThisResource(): void
    {
        $expected = resource_identifier();

        // Absent resource is accepted (the audience is bound regardless); an exact or trailing-slash
        // match is accepted; any other target is refused.
        self::assertTrue(resource_request_allowed('', $expected));
        self::assertTrue(resource_request_allowed($expected, $expected));
        self::assertTrue(resource_request_allowed($expected . '/', $expected));
        self::assertFalse(resource_request_allowed('https://evil.test/wp-json/mcp/vormbyte-oauth', $expected));
        self::assertFalse(resource_request_allowed('https://example.test/wp-json/mcp/vormbyte', $expected));
    }

    public function testAccessTokenAudienceIsTheResourceNotTheClient(): void
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($resource);
        $pem = '';
        openssl_pkey_export($resource, $pem);

        $entity = new AccessTokenEntity();
        $entity->setPrivateKey(new CryptKey($pem, passPhrase: null, keyPermissionsCheck: false));
        $entity->setIdentifier('tok_test');
        $entity->setUserIdentifier('42');
        $entity->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        $entity->addScope(self::scope('mcp'));

        $aud = self::jwtPayload((string) $entity)['aud'] ?? null;
        // A single audience may be encoded as a string or a one-element array, per JWT.
        if (is_array($aud)) {
            $aud = $aud[0] ?? null;
        }

        self::assertSame(resource_identifier(), $aud);
    }

    private static function scope(string $identifier): ScopeEntityInterface
    {
        return new class ($identifier) implements ScopeEntityInterface {
            public function __construct(private string $identifier)
            {
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }

            public function jsonSerialize(): mixed
            {
                return $this->identifier;
            }
        };
    }

    /** @return array<string, mixed> */
    private static function jwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $json = base64_decode(strtr($parts[1] ?? '', '-_', '+/'), strict: false);
        $decoded = json_decode((string) $json, associative: true);

        return is_array($decoded) ? $decoded : [];
    }
}
