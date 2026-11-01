<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use Vormbyte\OAuth\Keys\KeyBootstrapError;
use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default_value = false): mixed
    {
        return $GLOBALS['vormbyte_test_options'][$option] ?? $default_value;
    }
}
if (!function_exists('add_option')) {
    function add_option(
        string $option,
        mixed $value = '',
        string $deprecated = '',
        mixed $autoload = null,
    ): bool {
        if (isset($GLOBALS['vormbyte_test_options'][$option])) {
            return false;
        }
        $GLOBALS['vormbyte_test_options'][$option] = $value;

        return true;
    }
}
if (!class_exists('WP_CLI')) {
    /** Minimal stand-in for the WP-CLI runner, capturing what the command would print. */
    class WP_CLI
    {
        /** @var array<string, callable> */
        public static array $commands = [];

        /** @var list<string> */
        public static array $output = [];

        public static function add_command(string $name, callable $callable): void
        {
            self::$commands[$name] = $callable;
        }

        public static function success(string $message): void
        {
            self::$output[] = 'success: ' . $message;
        }

        public static function error(string $message): void
        {
            self::$output[] = 'error: ' . $message;
        }
    }
}

require_once __DIR__ . '/../../includes/oauth/keys.php';
require_once __DIR__ . '/../../includes/oauth/cli.php';

use function Vormbyte\OAuth\Keys\derive_public_key;
use function Vormbyte\OAuth\Keys\discover_openssl_configs;
use function Vormbyte\OAuth\Keys\drain_openssl_errors;
use function Vormbyte\OAuth\Keys\failure_message;
use function Vormbyte\OAuth\Keys\generate_private_key;
use function Vormbyte\OAuth\Keys\generation_failure_message;

/**
 * OAuth key bootstrap diagnostics.
 *
 * Key generation fails on environments whose PHP process started without a usable OPENSSL_CONF
 * (LocalWP on Windows is the reported one). OpenSSL only reads that variable at process start, so
 * the failure used to surface as a bare "openssl_pkey_new failed" with no way to act on it. The
 * generation now retries with a configuration file it can discover, and every failure message
 * carries the drained OpenSSL error queue plus the configuration path involved: never key material.
 */
final class KeyBootstrapTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['vormbyte_test_options']);
        WP_CLI::$output = [];
        WP_CLI::$commands = [];
        drain_openssl_errors();
    }

    public function testHappyPathStillReturnsAUsableKeyPair(): void
    {
        $private_pem = generate_private_key();

        self::assertStringStartsWith('-----BEGIN', $private_pem);
        self::assertNotFalse(openssl_pkey_get_private($private_pem));
        self::assertStringContainsString('PUBLIC KEY', derive_public_key($private_pem));
    }

    public function testParseFailureNamesTheOperationAndEveryOpenSslReason(): void
    {
        try {
            derive_public_key('-----BEGIN PRIVATE KEY-----not a key-----END PRIVATE KEY-----');
            self::fail('expected a KeyBootstrapError');
        } catch (KeyBootstrapError $e) {
            self::assertStringContainsString('openssl_pkey_get_private failed.', $e->getMessage());
            // The drained queue, not a bare operation name.
            self::assertStringContainsString('OpenSSL reported: error:', $e->getMessage());
        }
    }

    public function testConfigurationFailureNamesOpensslConfAndTheRecoveryCommand(): void
    {
        $message = generation_failure_message(
            ['error:07000068:configuration file routines::variable has no value'],
            ['/opt/php/extras/ssl/openssl.cnf'],
        );

        self::assertStringContainsString('openssl_pkey_new failed.', $message);
        self::assertStringContainsString('OPENSSL_CONF', $message);
        // The environment variable, not putenv(), is the only thing that fixes the process.
        self::assertStringContainsString('putenv() from PHP is too late', $message);
        // env[] is a PHP-FPM pool directive. php.ini has no equivalent, so naming it there sends
        // the operator to edit a file where the setting is silently ignored.
        self::assertStringNotContainsString('php.ini env[', $message);
        self::assertStringContainsString('php.ini has no directive for this', $message);
        self::assertStringContainsString('env[OPENSSL_CONF]', $message);
        self::assertStringContainsString('PHP-FPM pool', $message);
        self::assertStringContainsString('/opt/php/extras/ssl/openssl.cnf', $message);
        self::assertStringContainsString('wp vormbyte oauth-keys generate', $message);
        self::assertStringContainsString('variable has no value', $message);
    }

    public function testUnrelatedFailureKeepsTheMessageFreeOfConfigurationAdvice(): void
    {
        $message = failure_message('openssl_pkey_get_private', ['error:0480006C:PEM routines::no start line']);

        self::assertStringNotContainsString('OPENSSL_CONF', $message);
        self::assertStringContainsString('OpenSSL reported: error:0480006C:PEM routines::no start line.', $message);
    }

    public function testMessageStatesWhenOpenSslQueuedNothing(): void
    {
        self::assertStringContainsString('no further detail', failure_message('openssl_pkey_export', []));
    }

    public function testDrainEmptiesTheWholeErrorQueue(): void
    {
        openssl_pkey_get_private('not a key');
        openssl_pkey_get_private('still not a key');

        self::assertNotSame([], drain_openssl_errors());
        self::assertSame([], drain_openssl_errors());
    }

    /**
     * The real failure: OPENSSL_CONF is exported into the PHP process but names a file that is not
     * there, which is what LocalWP's web process effectively looks like. putenv() cannot reproduce
     * it (PHP caches the value at startup), so the generation runs in a child process.
     */
    public function testGenerationRecoversFromAnUnusableEnvironmentConfiguration(): void
    {
        if (DIRECTORY_SEPARATOR !== '/' || !function_exists('proc_open') || PHP_BINARY === '') {
            self::markTestSkipped('needs a POSIX shell-free child process');
        }

        $missing = sys_get_temp_dir() . '/vormbyte-missing-openssl.cnf';
        self::assertFileDoesNotExist($missing);

        // Same interpreter, same filesystem: whatever this process would discover once OPENSSL_CONF
        // is useless is exactly what the child can fall back to.
        $previous = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF=' . $missing);
        $fallbacks = discover_openssl_configs();
        putenv($previous === false ? 'OPENSSL_CONF' : 'OPENSSL_CONF=' . $previous);

        $result = $this->generateInChildProcess($missing);

        if ($fallbacks === []) {
            self::assertStringStartsWith('FAILED:', $result);
            self::assertStringContainsString('OPENSSL_CONF', $result);
            self::assertStringContainsString('wp vormbyte oauth-keys generate', $result);

            return;
        }

        self::assertSame('GENERATED', $result, 'the retry with a discovered config should rescue the key');
    }

    public function testCliCommandReportsUsableKeysWithoutPrintingKeyMaterial(): void
    {
        Vormbyte\OAuth\Cli\generate_command();

        self::assertCount(1, WP_CLI::$output);
        self::assertStringStartsWith('success: ', WP_CLI::$output[0]);
        self::assertStringContainsString('created', WP_CLI::$output[0]);
        self::assertStringNotContainsString('PRIVATE KEY', WP_CLI::$output[0]);
        self::assertStringNotContainsString(
            (string) ($GLOBALS['vormbyte_test_options']['vormbyte_oauth_encryption_key'] ?? 'unset'),
            WP_CLI::$output[0],
        );

        // A second run must not replace anything and must still report the keys as usable.
        WP_CLI::$output = [];
        Vormbyte\OAuth\Cli\generate_command();

        self::assertStringContainsString('already existed', WP_CLI::$output[0]);
        self::assertStringNotContainsString('PRIVATE KEY', WP_CLI::$output[0]);
    }

    public function testCliRegistrationExposesTheBootstrapCommand(): void
    {
        if (!defined('WP_CLI')) {
            define('WP_CLI', true);
        }

        Vormbyte\OAuth\Cli\register();

        self::assertArrayHasKey('vormbyte oauth-keys generate', WP_CLI::$commands);
    }

    private function generateInChildProcess(string $openssl_conf): string
    {
        $keys = realpath(__DIR__ . '/../../includes/oauth/keys.php');
        self::assertIsString($keys);

        $code = 'define("ABSPATH", "/");'
            . 'require ' . var_export($keys, true) . ';'
            . 'try { $pem = Vormbyte\\OAuth\\Keys\\generate_private_key();'
            . 'echo strpos($pem, "-----BEGIN") === 0 ? "GENERATED" : "UNEXPECTED"; }'
            . 'catch (Throwable $e) { echo "FAILED: " . $e->getMessage(); }';

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            [PHP_BINARY, '-r', $code],
            $descriptors,
            $pipes,
            null,
            ['OPENSSL_CONF' => $openssl_conf, 'PATH' => (string) getenv('PATH')],
        );
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        self::assertSame('', trim($stderr), 'the child process should not emit PHP errors');

        return trim($stdout);
    }
}
