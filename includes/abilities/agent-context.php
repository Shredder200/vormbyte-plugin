<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('vormbyte/agent-context', [
    'label' => __('Agent Context', domain: 'vormbyte'),
    // Read mode offers no skills, so its description does not mention them.
    'description' => function_exists('vormbyte_is_read_mode') && vormbyte_is_read_mode()
        ? __('Return transport-neutral Vormbyte read-mode guidance and environment.', domain: 'vormbyte')
        : __('Return transport-neutral Vormbyte site guidance, environment, and skill summaries.', domain: 'vormbyte'),
    'category' => 'context',
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'server' => ['type' => 'object'],
            'instructions' => ['type' => 'string'],
            'skills' => ['type' => 'array', 'items' => ['type' => 'object']],
            'environment' => ['type' => 'object'],
            'site_profile' => ['type' => 'object'],
            'read_mode' => [
                'type' => 'object',
                'properties' => ['active' => ['type' => 'boolean']],
                'required' => ['active'],
            ],
        ],
        'required' => ['server', 'instructions', 'skills', 'environment', 'read_mode'],
    ],
    'execute_callback' => 'vormbyte_build_agent_context',
    'permission_callback' => 'vormbyte_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'annotations' => [
            'readonly' => true,
            'destructive' => false,
            'idempotent' => true,
        ],
        'mcp' => ['public' => true, 'type' => 'tool'],
    ],
]);

/**
 * Reuse the same instruction and skill source/filter pipeline as existing integrations.
 *
 * @return array{
 *     server: array<string, mixed>,
 *     instructions: string,
 *     skills: list<array{slug: string, description: string, source: string}>,
 *     environment: array{wordpress_version: string, php_version: string, locale: string},
 *     site_profile: array<string, mixed>|object,
 *     read_mode: array{active: bool}
 * }
 */
function vormbyte_build_agent_context(): array
{
    $instructions = function_exists('vormbyte_agent_instructions')
        ? vormbyte_agent_instructions()
        : (string) apply_filters('vormbyte_discover_abilities_instructions', vormbyte_build_server_instructions());

    // Read mode offers no skills, so their sources and loaders are not consulted at all.
    $read_mode = function_exists('vormbyte_is_read_mode') && vormbyte_is_read_mode();
    $skills = [];
    foreach ($read_mode ? [] : \Vormbyte\Skills\Sources\discoverable('agentic') as $skill) {
        $skills[] = [
            'slug' => (string) ($skill['slug'] ?? ''),
            'description' => (string) ($skill['description'] ?? ''),
            'source' => (string) ($skill['source'] ?? ''),
        ];
    }

    return [
        'server' => vormbyte_server_compatibility(),
        'instructions' => $instructions,
        'skills' => $skills,
        'environment' => [
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'locale' => get_locale(),
        ],
        // How the site is built (theme type, page builders and their storage, key plugins), structured for
        // clients that want to reason over it rather than parse the instructions. Read mode leaves it out.
        'site_profile' => $read_mode || !function_exists('vormbyte_site_profile_or_empty')
            ? (object) []
            : (object) vormbyte_site_profile_or_empty(),
        // Whether read mode is on, for clients such as Vormbyte HQ: the instructions say so only as translated text.
        'read_mode' => ['active' => $read_mode],
    ];
}
