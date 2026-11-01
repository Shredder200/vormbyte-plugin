<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Internal compatibility contract shared by metadata, startup gates, and agent context.
 */
define(constant_name: 'VORMBYTE_VERSION', value: '1.12.10');
define(constant_name: 'VORMBYTE_REST_API_VERSION', value: 1);
define(constant_name: 'VORMBYTE_MINIMUM_WORDPRESS_VERSION', value: '6.9');

/**
 * Return the feature signals implemented by this build.
 *
 * Features remain false until their owning implementation session lands. This makes an in-progress
 * build fail closed at the public compatibility gate instead of advertising a surface it cannot serve.
 *
 * @return array{
 *     abilities_bearer_auth: bool,
 *     agent_context: bool,
 *     rest_skills: bool,
 *     generalized_execution_shim: bool
 * }
 */
function vormbyte_rest_api_features(): array
{
    return [
        'abilities_bearer_auth' => true,
        'agent_context' => true,
        'rest_skills' => true,
        'generalized_execution_shim' => true,
    ];
}

/**
 * Return the installed WordPress version without coupling metadata to an HTTP request.
 */
function vormbyte_wordpress_version(): string
{
    return get_bloginfo('version');
}

/**
 * Whether this WordPress installation has the minimum Abilities API generation Vormbyte supports.
 */
function vormbyte_wordpress_abilities_supported(?string $wordpress_version = null): bool
{
    $wordpress_version ??= vormbyte_wordpress_version();

    return $wordpress_version !== ''
    && version_compare($wordpress_version, VORMBYTE_MINIMUM_WORDPRESS_VERSION, operator: '>=');
}

/**
 * Stable compatibility block published before and after authentication.
 *
 * @return array{
 *     plugin_version: string,
 *     rest_api_version: int,
 *     wordpress_version: string,
 *     minimum_wordpress_version: string,
 *     features: array<string, bool>
 * }
 */
function vormbyte_server_compatibility(): array
{
    return [
        'plugin_version' => VORMBYTE_VERSION,
        'rest_api_version' => VORMBYTE_REST_API_VERSION,
        'wordpress_version' => vormbyte_wordpress_version(),
        'minimum_wordpress_version' => VORMBYTE_MINIMUM_WORDPRESS_VERSION,
        'features' => vormbyte_rest_api_features(),
    ];
}

/**
 * Register the unsupported-WordPress administrator warning when the Abilities API cannot be used.
 */
function vormbyte_register_wordpress_compatibility_notice(): void
{
    if (vormbyte_wordpress_abilities_supported()) {
        return;
    }

    add_action('admin_notices', callback: 'vormbyte_render_wordpress_compatibility_notice');
    add_action('network_admin_notices', callback: 'vormbyte_render_wordpress_compatibility_notice');
}

/**
 * Explain why Ability registration and its REST shim were skipped.
 */
function vormbyte_render_wordpress_compatibility_notice(): void
{
    if (!vormbyte_current_user_can_manage() || !vormbyte_admin_ui_visible()) {
        return;
    }

    wp_admin_notice(
        sprintf(
            esc_html__(
                'Vormbyte requires WordPress %1$s or newer for the Abilities API. WordPress %2$s is installed, so Vormbyte Ability and REST registration is disabled.',
                domain: 'vormbyte',
            ),
            esc_html(VORMBYTE_MINIMUM_WORDPRESS_VERSION),
            esc_html(vormbyte_wordpress_version()),
        ),
        ['type' => 'warning', 'dismissible' => false],
    );
}
