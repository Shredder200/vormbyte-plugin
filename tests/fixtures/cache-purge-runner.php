<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Exercises the OAuth discovery cache invalidation under a minimal WordPress emulation and prints
 * what happened, as JSON.
 *
 * A separate process per scenario is what makes the "no cache plugin is installed" case meaningful:
 * the purge APIs are global functions, actions and classes, so whether they exist has to be decided
 * before anything loads and cannot be undone inside a shared PHPUnit process.
 *
 * The emulation models the parts of WordPress this code depends on: `did_action()` counts,
 * `has_action()` answers for a named callback, and the option hooks fire the way core fires them,
 * that is only when the stored value actually changed.
 *
 * Scenarios (second argument):
 * - all-caches:          every supported cache plugin present; one effective option change.
 * - no-caches:           none of them present; one effective option change.
 * - unrelated-option:    a write to an option that is none of ours.
 * - multiple-writes:     the disable path, which stores one option and deletes the other.
 * - early-write:         the option changes before `plugins_loaded`, when caches have not hooked yet.
 * - discovery-request:   a request for a discovery path, with abilities off (nothing serves it).
 * - subdirectory-request: the same, on an install living under a path.
 * - trailing-slash-request: the same path as discovery-request, with the trailing slash a server may add.
 * - unrelated-request:   a request for another path.
 *
 * Usage: php cache-purge-runner.php <plugin-root> <scenario>
 */

namespace {
    const VORMBYTE_TEST_SCENARIOS = [
        'all-caches',
        'no-caches',
        'unrelated-option',
        'multiple-writes',
        'early-write',
        'init-write',
        'enable-domain',
        'kinsta-success',
        'kinsta-failure',
        'kinsta-unsupported',
        'discovery-request',
        'subdirectory-request',
        'trailing-slash-request',
        'unrelated-request',
    ];

    if ($argc !== 3 || !in_array($argv[2], VORMBYTE_TEST_SCENARIOS, strict: true)) {
        fwrite(
            STDERR,
            'Usage: php cache-purge-runner.php <plugin-root> <' . implode('|', VORMBYTE_TEST_SCENARIOS) . ">\n",
        );
        exit(2);
    }

    define('ABSPATH', '/');

    $GLOBALS['vormbyte_hooks'] = [];
    $GLOBALS['vormbyte_did'] = [];
    $GLOBALS['vormbyte_options'] = [];
    $GLOBALS['vormbyte_options_at_purge'] = [];
    $GLOBALS['vormbyte_purge_calls'] = [];
    $GLOBALS['vormbyte_headers'] = [];
    $GLOBALS['vormbyte_home_url'] = 'https://example.test';
    $GLOBALS['vormbyte_kinsta_requests'] = [];

    function add_action(string $hook, callable|string $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['vormbyte_hooks'][$hook][] = ['priority' => $priority, 'callback' => $callback];

        return true;
    }

    function has_action(string $hook, callable|string|false $callback = false): bool|int
    {
        if (!isset($GLOBALS['vormbyte_hooks'][$hook])) {
            return false;
        }
        if ($callback === false) {
            return true;
        }
        foreach ($GLOBALS['vormbyte_hooks'][$hook] as $registered) {
            if ($registered['callback'] === $callback) {
                return $registered['priority'];
            }
        }

        return false;
    }

    function did_action(string $hook): int
    {
        return $GLOBALS['vormbyte_did'][$hook] ?? 0;
    }

    function do_action(string $hook, mixed ...$args): void
    {
        $GLOBALS['vormbyte_did'][$hook] = ($GLOBALS['vormbyte_did'][$hook] ?? 0) + 1;
        $callbacks = $GLOBALS['vormbyte_hooks'][$hook] ?? [];
        usort($callbacks, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);
        foreach ($callbacks as $registered) {
            ($registered['callback'])(...$args);
        }
    }

    function home_url(string $path = ''): string
    {
        return $GLOBALS['vormbyte_home_url'] . $path;
    }
}

namespace Vormbyte\OAuth {
    function resource_identifier(): string
    {
        return \home_url('/wp-json/mcp/vormbyte-oauth');
    }
}

namespace Vormbyte\CachePurge {
    // Namespaced overrides: the production code calls these unqualified, so these definitions win
    // over the global ones and the runner can record what a real request would have sent.
    function headers_sent(): bool
    {
        return false;
    }

    function header(string $header): void
    {
        $GLOBALS['vormbyte_headers'][] = $header;
    }
}

namespace {
    /**
     * Core's own behaviour: an update that does not change the stored value fires nothing.
     */
    function vormbyte_test_update_option(string $option, string $value): void
    {
        $existing = $GLOBALS['vormbyte_options'][$option] ?? null;
        if ($existing === $value) {
            return;
        }
        $GLOBALS['vormbyte_options'][$option] = $value;
        do_action($existing === null ? 'added_option' : 'updated_option', $option);
    }

    function vormbyte_test_delete_option(string $option): void
    {
        if (!array_key_exists($option, $GLOBALS['vormbyte_options'])) {
            return;
        }
        unset($GLOBALS['vormbyte_options'][$option]);
        do_action('deleted_option', $option);
    }

    /**
     * @param string|list<string> $urls
     */
    function vormbyte_test_record(string $api, string|array $urls): void
    {
        foreach ((array) $urls as $url) {
            $GLOBALS['vormbyte_purge_calls'][$api][] = $url;
        }
    }

    function vormbyte_test_install_caches(): void
    {
        add_action('litespeed_purge_url', static fn(string $url) => vormbyte_test_record('litespeed-cache', $url));
        add_action(
            'cache_enabler_clear_page_cache_by_url',
            static fn(string $url) => vormbyte_test_record('cache-enabler', $url),
        );
        add_action('wpfc_clear_cache_by_url', static fn(string $url) => vormbyte_test_record('wp-fastest-cache', $url));

        require_once __DIR__ . '/cache-purge-plugins.php';

        $GLOBALS['nginx_purger'] = new VormbyteTestNginxPurger();
        // Kinsta publishes its global while loading, but its cache objects only exist at init 5.
        $GLOBALS['kinsta_cache'] = new stdClass();
        add_action('init', static function (): void {
            $GLOBALS['kinsta_cache']->kinsta_cache = (object) ['config' => ['immediate_path' => 'https://localhost/kinsta-clear-cache/v2/immediate']];
            $GLOBALS['kinsta_cache']->kinsta_cache_purge = new VormbyteTestKinstaPurger();
        }, 5);
    }

    $vormbyte_scenario = $argv[2];

    if (in_array($vormbyte_scenario, ['all-caches', 'init-write', 'enable-domain'], true)) {
        vormbyte_test_install_caches();
    }
    if ($vormbyte_scenario === 'subdirectory-request') {
        $GLOBALS['vormbyte_home_url'] = 'https://example.test/subsite';
    }

    require $argv[1] . '/includes/cache-purge.php';
    \Vormbyte\CachePurge\register();

    add_action(
        'vormbyte_purged_discovery_urls',
        static function (array $urls): void {
            vormbyte_test_record('vormbyte_purged_discovery_urls', $urls);
            $GLOBALS['vormbyte_options_at_purge'] = $GLOBALS['vormbyte_options'];
        },
    );

    $vormbyte_deferred = false;

    if ($vormbyte_scenario === 'early-write') {
        // Abilities are enabled while plugins are still loading, before the cache plugins have
        // published their purge API.
        vormbyte_test_update_option('vormbyte_ai_abilities_enabled', '1');
        $vormbyte_deferred = did_action('vormbyte_purged_discovery_urls') === 0
            && has_action('shutdown', 'Vormbyte\\CachePurge\\purge_discovery_urls') !== false;
        vormbyte_test_install_caches();
    }

    if ($vormbyte_scenario === 'init-write') {
        add_action('init', static function (): void {
            vormbyte_test_update_option('vormbyte_ai_abilities_enabled', '1');
            vormbyte_test_update_option('vormbyte_ai_abilities_domain', 'example.test');
        }, 1);
    }

    do_action('plugins_loaded');
    $vormbyte_purged_before_init = did_action('vormbyte_purged_discovery_urls');
    // Discovery-request scenarios run init after assigning REQUEST_URI below.
    if (!str_ends_with($vormbyte_scenario, '-request')) {
        do_action('init');
        do_action('wp_loaded');
    }

    $vormbyte_successful_caches = [];
    if (str_starts_with($vormbyte_scenario, 'kinsta-')) {
        require_once __DIR__ . '/cache-purge-plugins.php';
        $GLOBALS['kinsta_cache'] = (object) [
            'kinsta_cache' => (object) ['config' => ['immediate_path' => 'https://localhost/kinsta-clear-cache/v2/immediate']],
            'kinsta_cache_purge' => $vormbyte_scenario === 'kinsta-unsupported' ? new stdClass() : new VormbyteTestKinstaPurger(),
        ];
        if ($vormbyte_scenario === 'kinsta-failure') {
            $GLOBALS['vormbyte_kinsta_response'] = ['response_code' => 503, 'error_code' => 0];
        }
        $vormbyte_successful_caches = \Vormbyte\CachePurge\purge_via_kinsta(\Vormbyte\CachePurge\discovery_urls()) ? ['kinsta'] : [];
    }

    switch ($vormbyte_scenario) {
        case 'enable-domain':
            vormbyte_test_update_option('vormbyte_ai_abilities_enabled', '1');
            vormbyte_test_update_option('vormbyte_ai_abilities_domain', 'example.test');
            break;
        case 'all-caches':
        case 'no-caches':
            vormbyte_test_update_option('vormbyte_ai_abilities_enabled', '1');
            // A second write of the same value: core fires nothing, so neither do we.
            vormbyte_test_update_option('vormbyte_ai_abilities_enabled', '1');
            break;
        case 'unrelated-option':
            vormbyte_test_update_option('blogname', 'Something else');
            break;
        case 'multiple-writes':
            vormbyte_test_install_caches();
            $GLOBALS['vormbyte_options']['vormbyte_ai_abilities_enabled'] = '1';
            $GLOBALS['vormbyte_options']['vormbyte_ai_abilities_domain'] = 'example.test';
            vormbyte_test_update_option('vormbyte_ai_abilities_enabled', '0');
            vormbyte_test_delete_option('vormbyte_ai_abilities_domain');
            break;
        case 'discovery-request':
            $_SERVER['REQUEST_URI'] = '/.well-known/oauth-protected-resource?ignored=1';
            do_action('init');
            break;
        case 'subdirectory-request':
            $_SERVER['REQUEST_URI'] = '/subsite/.well-known/openid-configuration';
            do_action('init');
            break;
        case 'trailing-slash-request':
            $_SERVER['REQUEST_URI'] = '/.well-known/oauth-protected-resource/';
            do_action('init');
            break;
        case 'unrelated-request':
            $_SERVER['REQUEST_URI'] = '/.well-known/apple-app-site-association';
            do_action('init');
            break;
    }

    $vormbyte_purged_before_shutdown = did_action('vormbyte_purged_discovery_urls');
    do_action('shutdown');

    echo json_encode([
        'purge_events' => did_action('vormbyte_purged_discovery_urls'),
        'calls' => $GLOBALS['vormbyte_purge_calls'],
        'deferred_before_plugins_loaded' => $vormbyte_deferred,
        'purged_before_init' => $vormbyte_purged_before_init,
        'purged_before_shutdown' => $vormbyte_purged_before_shutdown,
        'options_at_purge' => $GLOBALS['vormbyte_options_at_purge'],
        'kinsta_requests' => $GLOBALS['vormbyte_kinsta_requests'],
        'successful_caches' => $vormbyte_successful_caches,
        'urls' => \Vormbyte\CachePurge\discovery_urls(),
        'donotcachepage' => defined('DONOTCACHEPAGE'),
        'litespeed_nocache' => did_action('litespeed_control_set_nocache'),
        'headers' => $GLOBALS['vormbyte_headers'],
    ], flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
