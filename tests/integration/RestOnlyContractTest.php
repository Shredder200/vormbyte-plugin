<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * HTTP-only Phase 1 acceptance harness. The fake WordPress edge models the fixed public REST/OAuth
 * contract while lower-level tests exercise the production handlers and real League OAuth grants.
 * This client intentionally has no protocol-session or JSON-RPC API.
 */
final class RestOnlyContractTest extends TestCase
{
    #[DataProvider('siteProvider')]
    public function testCompleteFullAccessRestWorkflow(string $siteUrl, string $forwardingMode): void
    {
        $server = $this->server($siteUrl, $forwardingMode);
        $metadata = $this->getMetadata($server, $siteUrl);
        self::assertSame(1, $metadata['vormbyte']['rest_api_version']);
        self::assertSame('1.11.2', $metadata['vormbyte']['plugin_version']);
        self::assertNotContains(false, $metadata['vormbyte']['features']);

        $token = $this->authorize($server, $siteUrl, 'mcp');
        $catalog = $this->listAllAbilities($server, $siteUrl, $token, $forwardingMode);
        self::assertSame(
            ['vormbyte/agent-context', 'vormbyte/read-file', 'vormbyte/skill-get', 'vormbyte/write-file', 'vendor/extension-action'],
            array_column($catalog, 'name'),
        );

        $item = $this->request($server, 'GET', $siteUrl . '/wp-json/wp-abilities/v1/abilities/vormbyte/read-file', token: $token, forwardingMode: $forwardingMode);
        self::assertSame(200, $item['status']);
        self::assertTrue($item['body']['meta']['annotations']['readonly']);

        $context = $this->runAbility($server, $siteUrl, 'vormbyte/agent-context', null, $token, $forwardingMode);
        self::assertSame($metadata['vormbyte'], $context['server']);

        $read = $this->runAbility($server, $siteUrl, 'vormbyte/read-file', ['path' => '/tmp/a'], $token, $forwardingMode);
        self::assertSame('contents', $read['content']);

        $mutation = $this->runAbility(
            $server,
            $siteUrl,
            'vormbyte/write-file',
            ['path' => '/tmp/a', 'content' => 'changed'],
            $token,
            $forwardingMode,
        );
        self::assertSame(['success' => true, 'bytes_written' => 7], $mutation);

        self::assertSame(
            ['extension' => 'extension'],
            $this->runAbility(
                $server,
                $siteUrl,
                'vendor/extension-action',
                ['value' => 'extension'],
                $token,
                $forwardingMode,
            ),
        );

        $skill = $this->runAbility(
            $server,
            $siteUrl,
            'vormbyte/skill-get',
            ['slug' => 'theme-maintenance'],
            $token,
            $forwardingMode,
        );
        self::assertTrue($skill['found']);
        self::assertStringContainsString('# Theme Maintenance', $skill['content']);

        $server->canManage = false;
        self::assertSame(
            403,
            $this->request(
                $server,
                'GET',
                $siteUrl . '/wp-json/wp-abilities/v1/abilities?page=1',
                token: $token,
                forwardingMode: $forwardingMode,
            )['status'],
        );
        self::assertSame(
            403,
            $this->request(
                $server,
                'POST',
                $siteUrl . '/wp-json/vormbyte/v1/abilities/vormbyte/read-file/run',
                ['input' => ['path' => '/tmp/a']],
                $token,
                $forwardingMode,
            )['status'],
        );

        $requestLog = json_encode($server->requests, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('/mcp/', $requestLog);
        self::assertStringNotContainsString('"jsonrpc"', $requestLog);
    }

    public function testMissingContextAfterSuccessfulPaginationFailsAtomicDiscovery(): void
    {
        $siteUrl = 'https://example.test/blog';
        $server = $this->server($siteUrl, 'direct', omitContext: true);
        $token = $this->authorize($server, $siteUrl, 'mcp');

        try {
            $this->discoverAtomically($server, $siteUrl, $token, 'direct');
            self::fail('Missing context must fail the complete discovery operation.');
        } catch (RuntimeException $error) {
            self::assertSame('server_unsupported: required agent context is missing', $error->getMessage());
        }

        self::assertNotEmpty(array_filter(
            $server->requests,
            static fn(array $request): bool => str_contains($request['url'], '/wp-abilities/v1/abilities?page=2'),
        ));
        self::assertNotEmpty(array_filter(
            $server->requests,
            static fn(array $request): bool => str_contains($request['url'], '/vormbyte/agent-context/run'),
        ));
    }

    /** @return iterable<string, array{string, string}> */
    public static function siteProvider(): iterable
    {
        yield 'root direct authorization header' => ['https://example.test', 'direct'];
        yield 'subdirectory CGI-forwarded authorization header' => ['https://example.test/blog', 'redirect'];
    }

    private function server(string $siteUrl, string $forwardingMode, bool $omitContext = false): object
    {
        return new class ($siteUrl, $forwardingMode, $omitContext) {
            /** @var list<array{method: string, url: string, body: mixed, headers: array<string, string>}> */
            public array $requests = [];
            public bool $canManage = true;
            /** @var array<string, string> */
            private array $codes = [];

            public function __construct(
                private string $siteUrl,
                private string $forwardingMode,
                private bool $omitContext,
            ) {
            }

            /**
             * @param array<string, string> $headers
             * @return array{status: int, body: mixed, headers: array<string, string>}
             */
            public function request(string $method, string $url, mixed $body, array $headers): array
            {
                $this->requests[] = compact('method', 'url', 'body', 'headers');
                $path = (string) parse_url($url, PHP_URL_PATH);
                $query = [];
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $basePath = rtrim((string) parse_url($this->siteUrl, PHP_URL_PATH), '/');
                $rest = $basePath . '/wp-json';

                if ($path === $basePath . '/.well-known/oauth-protected-resource') {
                    return $this->response(200, [
                        'resource' => $this->siteUrl . '/wp-json/mcp/vormbyte-oauth',
                        'authorization_servers' => [$this->siteUrl],
                        'bearer_methods_supported' => ['header'],
                        'scopes_supported' => ['mcp'],
                        'vormbyte' => $this->compatibility(),
                    ]);
                }
                if ($path === $basePath . '/wp-admin/admin.php' && ($query['page'] ?? '') === 'vormbyte-oauth-authorize') {
                    $scope = (string) ($query['scope'] ?? '');
                    $code = 'code-' . str_replace(':', '-', $scope);
                    $this->codes[$code] = $scope;
                    return $this->response(302, null, ['Location' => 'http://127.0.0.1/callback?code=' . $code]);
                }
                if ($path === $rest . '/vormbyte/v1/oauth/token') {
                    $code = is_array($body) ? (string) ($body['code'] ?? '') : '';
                    $scope = $this->codes[$code] ?? '';
                    return $scope === ''
                        ? $this->response(400, ['error' => 'invalid_grant'])
                        : $this->response(200, ['access_token' => 'token-' . $scope, 'scope' => $scope]);
                }

                $scope = $this->authenticatedScope($headers);
                if ($scope === null) {
                    return $this->response(401, ['code' => 'rest_oauth_required']);
                }
                if (!$this->canManage) {
                    return $this->response(403, ['code' => 'rest_forbidden']);
                }
                if ($path === $rest . '/wp-abilities/v1/abilities') {
                    $page = (int) ($query['page'] ?? 1);
                    $records = $page === 1
                        ? [['name' => 'vormbyte/agent-context'], ['name' => 'vormbyte/read-file']]
                        : [['name' => 'vormbyte/skill-get'], ['name' => 'vormbyte/write-file'], ['name' => 'vendor/extension-action']];
                    return $this->response(200, $records, ['X-WP-TotalPages' => '2']);
                }
                if ($path === $rest . '/wp-abilities/v1/abilities/vormbyte/read-file') {
                    return $this->response(200, [
                        'name' => 'vormbyte/read-file',
                        'meta' => ['show_in_rest' => true, 'annotations' => ['readonly' => true]],
                    ]);
                }
                if (str_ends_with($path, '/vormbyte/agent-context/run')) {
                    return $this->omitContext
                        ? $this->response(404, ['code' => 'vormbyte_ability_not_found'])
                        : $this->response(200, [
                            'server' => $this->compatibility(),
                            'instructions' => 'Site guidance',
                            'skills' => [['slug' => 'theme-maintenance', 'description' => 'Theme guidance', 'source' => 'user-cpt']],
                            'environment' => ['wordpress_version' => '6.9.2', 'php_version' => '8.3.8', 'locale' => 'en_US'],
                        ]);
                }
                if (str_ends_with($path, '/vormbyte/read-file/run')) {
                    return $this->response(200, ['content' => 'contents']);
                }
                if (str_ends_with($path, '/vormbyte/write-file/run')) {
                    return $scope !== 'mcp'
                        ? $this->response(403, ['code' => 'rest_oauth_error'])
                        : $this->response(200, ['success' => true, 'bytes_written' => 7]);
                }
                if (str_ends_with($path, '/vormbyte/skill-get/run')) {
                    return $this->response(200, [
                        'found' => true,
                        'slug' => 'theme-maintenance',
                        'content' => "# Theme Maintenance\n\nInstructions",
                    ]);
                }
                if (str_ends_with($path, '/vendor/extension-action/run')) {
                    return $scope !== 'mcp'
                        ? $this->response(403, ['code' => 'rest_oauth_error'])
                        : $this->response(200, ['extension' => $body['input']['value'] ?? null]);
                }

                return $this->response(404, ['code' => 'rest_no_route']);
            }

            /** @return array<string, mixed> */
            private function compatibility(): array
            {
                return [
                    'plugin_version' => '1.11.2',
                    'rest_api_version' => 1,
                    'wordpress_version' => '6.9.2',
                    'minimum_wordpress_version' => '6.9',
                    'features' => [
                        'abilities_bearer_auth' => true,
                        'agent_context' => true,
                        'rest_skills' => true,
                        'generalized_execution_shim' => true,
                    ],
                ];
            }

            /** @param array<string, string> $headers */
            private function authenticatedScope(array $headers): ?string
            {
                $headerName = $this->forwardingMode === 'redirect' ? 'Redirect-Authorization' : 'Authorization';
                $authorization = $headers[$headerName] ?? '';
                return str_starts_with($authorization, 'Bearer token-')
                    ? substr($authorization, strlen('Bearer token-'))
                    : null;
            }

            /**
             * @param array<string, string> $headers
             * @return array{status: int, body: mixed, headers: array<string, string>}
             */
            private function response(int $status, mixed $body, array $headers = []): array
            {
                return ['status' => $status, 'body' => $body, 'headers' => $headers];
            }
        };
    }

    /** @return array<string, mixed> */
    private function getMetadata(object $server, string $siteUrl): array
    {
        $response = $this->request($server, 'GET', $siteUrl . '/.well-known/oauth-protected-resource');
        self::assertSame(200, $response['status']);
        return $response['body'];
    }

    private function authorize(object $server, string $siteUrl, string $scope): string
    {
        $authorization = $this->request(
            $server,
            'GET',
            $siteUrl . '/wp-admin/admin.php?page=vormbyte-oauth-authorize&response_type=code&scope=' . rawurlencode($scope),
        );
        self::assertSame(302, $authorization['status']);
        $query = [];
        parse_str((string) parse_url($authorization['headers']['Location'], PHP_URL_QUERY), $query);
        $token = $this->request($server, 'POST', $siteUrl . '/wp-json/vormbyte/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $query['code'] ?? '',
        ]);
        self::assertSame(200, $token['status']);
        self::assertSame($scope, $token['body']['scope']);
        return $token['body']['access_token'];
    }

    /** @return list<array<string, mixed>> */
    private function listAllAbilities(object $server, string $siteUrl, string $token, string $forwardingMode): array
    {
        $all = [];
        for ($page = 1; $page <= 2; $page++) {
            $response = $this->request(
                $server,
                'GET',
                $siteUrl . '/wp-json/wp-abilities/v1/abilities?page=' . $page,
                token: $token,
                forwardingMode: $forwardingMode,
            );
            self::assertSame(200, $response['status']);
            self::assertSame('2', $response['headers']['X-WP-TotalPages']);
            $all = array_merge($all, $response['body']);
        }
        return $all;
    }

    /** @return array<string, mixed> */
    private function runAbility(
        object $server,
        string $siteUrl,
        string $ability,
        mixed $input,
        string $token,
        string $forwardingMode,
    ): array {
        $response = $this->request(
            $server,
            'POST',
            $siteUrl . '/wp-json/vormbyte/v1/abilities/' . $ability . '/run',
            ['input' => $input],
            $token,
            $forwardingMode,
        );
        self::assertSame(200, $response['status']);
        return $response['body'];
    }

    /** @return array{abilities: list<array<string, mixed>>, context: array<string, mixed>} */
    private function discoverAtomically(object $server, string $siteUrl, string $token, string $forwardingMode): array
    {
        $abilities = $this->listAllAbilities($server, $siteUrl, $token, $forwardingMode);
        $contextResponse = $this->request(
            $server,
            'POST',
            $siteUrl . '/wp-json/vormbyte/v1/abilities/vormbyte/agent-context/run',
            ['input' => null],
            $token,
            $forwardingMode,
        );
        if ($contextResponse['status'] !== 200) {
            throw new RuntimeException('server_unsupported: required agent context is missing');
        }
        return ['abilities' => $abilities, 'context' => $contextResponse['body']];
    }

    /**
     * @return array{status: int, body: mixed, headers: array<string, string>}
     */
    private function request(
        object $server,
        string $method,
        string $url,
        mixed $body = null,
        ?string $token = null,
        string $forwardingMode = 'direct',
    ): array {
        $headers = ['Accept' => 'application/json'];
        if ($token !== null) {
            $headers[$forwardingMode === 'redirect' ? 'Redirect-Authorization' : 'Authorization'] = 'Bearer ' . $token;
        }
        return $server->request($method, $url, $body, $headers);
    }
}
