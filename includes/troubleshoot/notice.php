<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Troubleshoot\Notice;

if (!defined('ABSPATH')) {
    exit();
}

const DISMISS_META = 'vormbyte_troubleshoot_dismissed';

/**
 * Regressions worth interrupting an admin for: conditions that (a) certainly break connections
 * that were working, and (b) cost nothing to detect (no HTTP, one small indexed query at most).
 * Anything fuzzier belongs in the on-demand troubleshooter, not a nag.
 *
 * @return list<string> Human-readable regression descriptions; empty when all is well.
 */
function regressions(): array
{
    $found = [];

    // Application Passwords turned off while Vormbyte passwords have actually been used.
    $status = \vormbyte_app_passwords_status();
    if (!$status['available'] && $status['reason'] === 'filtered') {
        foreach (\vormbyte_get_mcp_passwords() as $password) {
            if (($password['last_used'] ?? null) === null) {
                continue;
            }
            $found[] = __(
                'Application Passwords have been disabled (likely by a security plugin), so AI clients connected with the password method cannot authenticate anymore.',
                domain: 'vormbyte',
            );
            break;
        }
    }

    // Site dropped to plain HTTP while OAuth connections are active: the endpoints are gone.
    if (
        !\vormbyte_oauth_transport_allowed()
        && get_option('vormbyte_oauth_schema_version', default_value: false) !== false
        && \Vormbyte\OAuth\ClientValidation\active_client_count() > 0
    ) {
        $found[] = __(
            'This site is no longer served over HTTPS, so the OAuth endpoints are disabled and connected AI clients cannot authenticate anymore.',
            domain: 'vormbyte',
        );
    }

    return $found;
}

function maybe_render(): void
{
    if (!\vormbyte_current_user_can_manage() || !\vormbyte_admin_ui_visible()) {
        return;
    }
    $found = regressions();
    if ($found === []) {
        return;
    }
    $hash = md5((string) wp_json_encode($found));
    $troubleshoot_url = admin_url('admin.php?page=vormbyte-troubleshoot');
    $message = '<p><strong>' . esc_html__('Vormbyte connections need attention.', domain: 'vormbyte') . '</strong></p>';
    foreach ($found as $regression) {
        $message .= '<p>' . esc_html($regression) . '</p>';
    }
    $message .=
        '<p><a href="'
        . esc_url($troubleshoot_url)
        . '">'
        . esc_html__('Troubleshoot', domain: 'vormbyte')
        . '</a></p>';

    \vormbyte_render_persistent_admin_notice($message, meta_key: DISMISS_META, dismiss_value: $hash, args: [
        'type' => 'error',
        'paragraph_wrap' => false,
    ]);
}
