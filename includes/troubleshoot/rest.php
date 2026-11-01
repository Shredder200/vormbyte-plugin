<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Troubleshoot\Rest;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit();
}

function register_routes(): void
{
    $permission = static fn(): bool => is_user_logged_in() && \vormbyte_current_user_can_manage();

    register_rest_route('vormbyte/v1', route: '/troubleshoot/run-checks', args: [
        'methods' => 'POST',
        'permission_callback' => $permission,
        'callback' => __NAMESPACE__ . '\\run_checks',
    ]);
}

function run_checks(WP_REST_Request $req): WP_REST_Response
{
    // @mago-expect analysis:mixed-assignment
    $raw = $req->get_json_params()['method'] ?? null;
    $method = in_array($raw, ['oauth', 'password'], strict: true) ? $raw : null;

    $checks = \Vormbyte\Troubleshoot\Checks\run_all($method);
    // run_all() already probed for security/edge layers; current_security_edge_layers() caches that
    // result for this request, so the report reuses it without a second self-request.
    $layers = \Vormbyte\Troubleshoot\Checks\current_security_edge_layers();
    $hosting = \Vormbyte\Hosting\Detector::current();
    $meta = [
        'site_url' => home_url('/'),
        'vormbyte_version' => defined('VORMBYTE_VERSION') ? VORMBYTE_VERSION : '',
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'method' => $method ?? '',
        'hosting' => $hosting['name'] ?? '',
    ];

    return new WP_REST_Response([
        'checks' => $checks,
        'layers' => $layers,
        'report' => \Vormbyte\Troubleshoot\Checks\build_support_report($meta, $layers, $checks),
    ]);
}
