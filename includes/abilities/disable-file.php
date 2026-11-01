<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Ability: Disable a sandbox file with a sidecar marker.
 */

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('vormbyte/disable-file', [
    'label' => __('Disable File', domain: 'vormbyte'),
    'description' => __(
        'Disables a PHP file in the sandbox (wp-content/vormbyte-sandbox/) without renaming its source. Vormbyte records the disabled state in an empty sidecar marker so PHP source is never exposed under a non-PHP filename. Only operates on files inside the sandbox directory. Idempotent: disabling an already-disabled file succeeds with disabled=false.',
        domain: 'vormbyte',
    ),
    'category' => 'filesystem',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'path' => [
                'type' => 'string',
                'description' => 'Path to the sandbox file to disable. Relative paths are resolved from ABSPATH. Must be inside wp-content/vormbyte-sandbox/.',
                'minLength' => 1,
            ],
        ],
        'required' => ['path'],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'original_path' => ['type' => 'string', 'description' => 'Absolute path of the original file.'],
            'disabled_path' => ['type' => 'string', 'description' => 'Absolute path of the disabled-state marker.'],
            'disabled' => ['type' => 'boolean', 'description' => 'Whether the disabled-state marker was created.'],
        ],
    ],
    'execute_callback' => 'vormbyte_disable_file',
    'permission_callback' => 'vormbyte_permission_callback',
    'meta' => [
        'show_in_rest' => true,
        'mcp' => ['public' => true],
        'annotations' => [
            'instructions' => implode("\n", [
                'SANDBOX NOTES:',
                '- Only files inside wp-content/vormbyte-sandbox/ (the PHP sandbox) can be disabled.',
                '- Disabling creates an empty sidecar marker; the PHP source keeps its .php filename.',
                '- To re-enable, use enable-file with the original PHP file path.',
                '- Safer than deleting: the file stays on disk for later re-use.',
            ]),
            'readonly' => false,
            'destructive' => false,
            'idempotent' => true,
        ],
    ],
]);

/**
 * Disable a sandbox PHP file by creating an empty sidecar marker.
 *
 * @param array $input Input with 'path'.
 * @return array|WP_Error
 */
function vormbyte_disable_file($input)
{
    $resolved = vormbyte_resolve_path((string) $input['path'], must_exist: true);
    if (is_wp_error($resolved)) {
        return $resolved;
    }

    $sandbox_check = vormbyte_validate_sandbox_path($resolved);
    if (is_wp_error($sandbox_check)) {
        return $sandbox_check;
    }

    if (!is_file($resolved)) {
        return new WP_Error('not_a_file', sprintf('Path is not a file: %s', $resolved), ['status' => 400]);
    }

    if (strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) !== 'php') {
        return new WP_Error('not_a_php_file', sprintf('Only sandbox PHP files can be disabled: %s', $resolved), [
            'status' => 400,
        ]);
    }

    $disabled_path = vormbyte_sandbox_disabled_marker_path($resolved);

    // Idempotent: already disabled.
    if (vormbyte_sandbox_file_is_disabled($resolved)) {
        return [
            'original_path' => $resolved,
            'disabled_path' => $disabled_path,
            'disabled' => false,
        ];
    }

    if (file_exists($disabled_path)) {
        return new WP_Error(
            'disabled_marker_exists',
            sprintf('The disabled marker path is occupied: %s', $disabled_path),
            ['status' => 409],
        );
    }

    if (!vormbyte_create_sandbox_disabled_marker($resolved)) {
        return new WP_Error('marker_create_failed', sprintf('Failed to disable file: %s', $resolved));
    }

    return [
        'original_path' => $resolved,
        'disabled_path' => $disabled_path,
        'disabled' => true,
    ];
}
