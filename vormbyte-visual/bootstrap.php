<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Visual;

if (!defined('ABSPATH')) {
    exit();
}

define(constant_name: 'VORMBYTE_VISUAL_VERSION', value: VORMBYTE_VERSION);
define(constant_name: 'VORMBYTE_VISUAL_PLUGIN_DIR', value: __DIR__ . '/');
define(constant_name: 'VORMBYTE_VISUAL_PLUGIN_URL', value: VORMBYTE_PLUGIN_URL . 'vormbyte-visual/');

require_once __DIR__ . '/includes/Workspace.php';
require_once __DIR__ . '/includes/BackendTools.php';

new \VormbyteVisual\Workspace();

function asset_version(string $asset_file): string
{
    $modified_time = file_exists($asset_file) ? filemtime($asset_file) : false;

    return is_int($modified_time) ? (string) $modified_time : (string) VORMBYTE_VISUAL_VERSION;
}

/**
 * Enqueue the builder tool bundle inside the Gutenberg block editor.
 */
add_action('enqueue_block_editor_assets', static function (): void {
    $asset_file = VORMBYTE_VISUAL_PLUGIN_DIR . 'dist/gutenberg.js';
    $version = asset_version($asset_file);

    wp_enqueue_script(
        'vormbyte-visual-gutenberg-tools',
        VORMBYTE_VISUAL_PLUGIN_URL . 'dist/gutenberg.js',
        ['wp-blocks', 'wp-data', 'wp-dom-ready', 'wp-editor'],
        $version,
        args: true,
    );

    wp_localize_script('vormbyte-visual-gutenberg-tools', object_name: 'vormbyteVisualData', l10n: [
        'restUrl' => esc_url_raw(rest_url('vormbyte-visual/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
        // In read mode the editor exposes only the tools that read editor state.
        'readMode' => function_exists('vormbyte_is_read_mode') && \vormbyte_is_read_mode(),
    ]);
});
