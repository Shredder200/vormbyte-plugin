<?php
// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }
}

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/connect-page.php';

final class ConnectPageNameTest extends TestCase
{
    public function testRootSiteRetainsHostOnlyDefault(): void
    {
        self::assertSame(
            'vormbyte-averylongexample',
            self::serverNameForUrl('https://www.averylongexample.com/'),
        );
        self::assertSame(
            'vormbyte-averylongexample',
            self::serverNameForUrl('https://www.averylongexample.com'),
        );
    }

    public function testRootSiteRetainsLegacyNormalizationEdgeCases(): void
    {
        self::assertSame('vormbyte-www-example-com', self::serverNameForUrl('https://WWW.Example.COM/'));
        self::assertSame('vormbyte-192-0-2-1', self::serverNameForUrl('http://192.0.2.1:8080/'));
        self::assertSame('vormbyte-abcdefghijklmno', self::serverNameForUrl('https://abcdefghijklmno.example/'));
        self::assertSame('vormbyte-xn--mnchen-3ya-d', self::serverNameForUrl('https://xn--mnchen-3ya.de/'));
    }

    public function testShortSubdirectoryIsIncludedInDefault(): void
    {
        self::assertSame('vormbyte-example-com-shop', self::serverNameForUrl('https://www.example.com/shop/'));
    }

    public function testLongSubdirectoriesUseDifferentBoundedDefaults(): void
    {
        $first = self::serverNameForUrl('https://www.example.com/very-long-storefront-one/');
        $second = self::serverNameForUrl('https://www.example.com/very-long-storefront-two/');

        self::assertNotSame($first, $second);
        self::assertLessThanOrEqual(25, strlen($first));
        self::assertLessThanOrEqual(25, strlen($second));
        self::assertMatchesRegularExpression('/^vormbyte-[a-z0-9]+(?:-[a-z0-9]+)*$/', $first);
        self::assertMatchesRegularExpression('/^vormbyte-[a-z0-9]+(?:-[a-z0-9]+)*$/', $second);
        self::assertSame($first, self::serverNameForUrl('https://www.example.com/very-long-storefront-one'));
    }

    public function testLongSubdirectoryHashIgnoresSchemeAndWwwPrefix(): void
    {
        $expected = self::serverNameForUrl('http://example.com/very-long-storefront-one/');

        self::assertSame($expected, self::serverNameForUrl('https://example.com/very-long-storefront-one/'));
        self::assertSame($expected, self::serverNameForUrl('https://www.example.com/very-long-storefront-one/'));
    }

    public function testLongSubdirectoryHashNormalizesDefaultPorts(): void
    {
        $expected = self::serverNameForUrl('https://example.com/very-long-storefront-one/');

        self::assertSame($expected, self::serverNameForUrl('https://example.com:443/very-long-storefront-one/'));
        self::assertSame($expected, self::serverNameForUrl('http://example.com:80/very-long-storefront-one/'));
    }

    public function testLongSubdirectoryHashIncludesNonDefaultExplicitPort(): void
    {
        self::assertNotSame(
            self::serverNameForUrl('https://example.com:8443/very-long-storefront-one/'),
            self::serverNameForUrl('https://example.com:9443/very-long-storefront-one/'),
        );
    }

    public function testSubdirectorySlugCollapsesHyphenRuns(): void
    {
        self::assertSame('vormbyte-ex-com-my-shop', self::serverNameForUrl('http://ex.com/my-_shop'));
        self::assertSame(
            'vormbyte-xn-mnchen-e61052',
            self::serverNameForUrl('http://xn--mnchen-3ya.de/blog'),
        );
    }

    public function testStoredHomeOptionWinsOverFilteredHomeUrl(): void
    {
        self::assertSame(
            'vormbyte-example-com',
            self::serverNameFromWordPressUrls('https://example.com/', 'https://example.com/de/'),
        );
        self::assertSame(
            'vormbyte-example-com-shop',
            self::serverNameFromWordPressUrls('not a URL', 'https://example.com/shop/'),
        );
        self::assertSame(
            'vormbyte-example-com-shop',
            self::serverNameFromWordPressUrls('', 'https://example.com/shop/'),
        );
        self::assertSame(
            'vormbyte-example-com',
            self::serverNameFromWordPressUrls('https://example.com/', 'https://example.com'),
        );
    }

    public function testPlainSiteNameDecodesHtmlEntities(): void
    {
        self::assertSame('A & B', vormbyte_plain_site_name('A &amp; B'));
        self::assertSame('', vormbyte_plain_site_name('&nbsp;'));
    }

    public function testDefaultServerNameKeepsASingleVormbyte(): void
    {
        self::assertSame('vormbyte-122-domain-com', self::serverNameForUrl('https://vormbyte122.domain.com/'));
        self::assertSame('vormbyte-yourdomainname-c', self::serverNameForUrl('https://vormbyte.yourdomainname.com/'));
        self::assertSame(
            'vormbyte-visual-122-domai',
            vormbyte_build_mcp_server_name_default('vormbyte122.domain.com', null, '', prefix: 'vormbyte-visual-'),
        );
    }

    public function testUnbrandedSiteNameDropsLeadingVormbyte(): void
    {
        self::assertSame('Demo', vormbyte_unbranded_site_name('Vormbyte Demo'));
        self::assertSame('Demo', vormbyte_unbranded_site_name('vormbyte | Demo'));
        self::assertSame('Demo', vormbyte_unbranded_site_name('Vormbyte — Demo'));
        self::assertSame('', vormbyte_unbranded_site_name('Vormbyte'));
        self::assertSame('Vormbytedesign', vormbyte_unbranded_site_name('Vormbytedesign'));
        self::assertSame('Demo Vormbyte', vormbyte_unbranded_site_name('Demo Vormbyte'));
    }

    public function testPlainSiteNameReplacesCharactersInvalidInWindowsFileNames(): void
    {
        self::assertSame('Acme - Shop', vormbyte_plain_site_name('Acme | Shop'));
        self::assertSame('A-B-C-D', vormbyte_plain_site_name('A/B\\C:D'));
        self::assertSame('Q-A', vormbyte_plain_site_name('"Q?" <A*>'));
        self::assertSame('Line one two', vormbyte_plain_site_name("Line one\ntwo"));
        self::assertSame('Well-known', vormbyte_plain_site_name('Well-known'));
        self::assertSame('nome-del-sito', vormbyte_plain_site_name('nome/|\\del-sito'));
        self::assertSame('nome-del-sito', vormbyte_plain_site_name('nome-|-del-sito'));
        self::assertSame('Acme - Shop', vormbyte_plain_site_name('Acme | | Shop'));
        self::assertSame('', vormbyte_plain_site_name(' | '));
    }

    private static function serverNameForUrl(string $site_url): string
    {
        $site = vormbyte_parse_mcp_server_site_url($site_url);
        self::assertNotNull($site);
        return vormbyte_build_mcp_server_name_default($site['host'], $site['port'], $site['path']);
    }

    private static function serverNameFromWordPressUrls(string $stored_home, string $filtered_home): string
    {
        $command = sprintf(
            '%s %s %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/../fixtures/connect-page-name-runner.php'),
            escapeshellarg($stored_home),
            escapeshellarg($filtered_home),
        );
        return trim((string) shell_exec($command));
    }
}
