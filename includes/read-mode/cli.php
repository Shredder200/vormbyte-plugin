<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * WP-CLI: `wp vormbyte read-mode status` and `wp vormbyte read-mode disable`. Turning read mode on is
 * Vormbyte Pro's command.
 */

namespace Vormbyte\ReadMode\Cli;

use WP_Error;

use function Vormbyte\ReadMode\describe_user;
use function Vormbyte\ReadMode\disable;
use function Vormbyte\ReadMode\status;

if (!defined('ABSPATH')) {
    exit();
}

function register(): void
{
    if (!defined('WP_CLI') || constant('WP_CLI') !== true || !class_exists('WP_CLI')) {
        return;
    }

    \WP_CLI::add_command('vormbyte read-mode status', __NAMESPACE__ . '\\status_command');
    \WP_CLI::add_command('vormbyte read-mode disable', __NAMESPACE__ . '\\disable_command');
}

/**
 * Show whether read mode is on for this site, and who changed it last.
 *
 * ## EXAMPLES
 *
 *     wp vormbyte read-mode status
 *     wp vormbyte read-mode status --url=https://example.com/subsite
 */
function status_command(): void
{
    $status = status();
    $labels = [
        'off' => __('off', domain: 'vormbyte'),
        'on' => __('on', domain: 'vormbyte'),
        'unreadable' => __('on (the saved setting is not valid)', domain: 'vormbyte'),
    ];

    \WP_CLI::line(sprintf(__('Read Mode: %s', domain: 'vormbyte'), $labels[$status['state']]));
    if ($status['updated_at'] > 0) {
        \WP_CLI::line(sprintf(
            __('Last change: %1$s by %2$s', domain: 'vormbyte'),
            (string) wp_date('Y-m-d H:i', $status['updated_at']),
            describe_user($status['updated_by']),
        ));
    }
}

/**
 * Turn read mode off for this site. AI agents can use every enabled ability again.
 *
 * ## OPTIONS
 *
 * [--yes]
 * : Skip the confirmation.
 *
 * ## EXAMPLES
 *
 *     wp vormbyte read-mode disable --yes
 *
 * @param list<string> $args
 * @param array<string, mixed> $assoc_args
 */
function disable_command(array $args, array $assoc_args): void
{
    \WP_CLI::confirm(
        __('Turn off Read Mode? Vormbyte will offer AI agents its write abilities again.', domain: 'vormbyte'),
        $assoc_args,
    );
    report(disable(user_id: 0, source: 'cli'), __('Read Mode is off.', domain: 'vormbyte'));
}

function report(?WP_Error $error, string $success): void
{
    if ($error instanceof WP_Error) {
        \WP_CLI::error($error->get_error_message());

        return;
    }
    \WP_CLI::success($success);
}
