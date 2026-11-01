<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Context;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Admin page for site-level context shown to connected agents. The saved
 * content is prepended to the Vormbyte discover-abilities instructions, while
 * the read-only preview shows the system instructions without that user layer.
 */

const INSTRUCTIONS_ENABLED_OPTION = 'vormbyte_instructions_enabled';

const INSTRUCTIONS_CONTENT_OPTION = 'vormbyte_instructions_content';

const LEGACY_PRO_INSTRUCTIONS_ENABLED_OPTION = 'nvp_instructions_enabled';

const LEGACY_PRO_INSTRUCTIONS_CONTENT_OPTION = 'nvp_instructions_content';

const OPTION_MISSING = '__vormbyte_option_missing__';

const USER_CONTEXT_FEATURE_ID = 'vormbyte/user-context';

function context_page_slug(): string
{
    return 'vormbyte-context';
}

/** @param array<string, scalar> $query */
function context_page_url(array $query = []): string
{
    return add_query_arg(array_merge(['page' => context_page_slug()], $query), admin_url('admin.php'));
}

function instructions_enabled_option_name(): string
{
    return INSTRUCTIONS_ENABLED_OPTION;
}

function instructions_content_option_name(): string
{
    return INSTRUCTIONS_CONTENT_OPTION;
}

function instructions_legacy_enabled_option_name(): string
{
    return LEGACY_PRO_INSTRUCTIONS_ENABLED_OPTION;
}

function instructions_legacy_content_option_name(): string
{
    return LEGACY_PRO_INSTRUCTIONS_CONTENT_OPTION;
}

function instructions_read_option_with_legacy(string $option_name, string $legacy_option_name, mixed $default): mixed
{
    /** @var mixed $value */
    $value = get_option($option_name, default_value: OPTION_MISSING);
    if ($value !== OPTION_MISSING) {
        return $value;
    }

    /** @var mixed $legacy_value */
    $legacy_value = get_option($legacy_option_name, default_value: OPTION_MISSING);
    if ($legacy_value !== OPTION_MISSING) {
        return $legacy_value;
    }

    return $default;
}

function instructions_update_content(string $content): void
{
    update_option(instructions_content_option_name(), $content);
    update_option(instructions_legacy_content_option_name(), $content);
}

function legacy_pro_context_loaded(): bool
{
    return (
        function_exists('\\Vormbyte\\Pro\\instructions_get_content')
        && function_exists('\\Vormbyte\\Pro\\instructions_is_enabled')
    );
}

function instructions_is_enabled(): bool
{
    try {
        return \Vormbyte\Features\features()->is_active(USER_CONTEXT_FEATURE_ID);
    } catch (\LogicException) {
        // Before the Features runtime initializes, retain the historical
        // value. Normal requests use only the central feature preference.
        return filter_var(
            instructions_read_option_with_legacy(
                option_name: instructions_enabled_option_name(),
                legacy_option_name: instructions_legacy_enabled_option_name(),
                default: true,
            ),
            FILTER_VALIDATE_BOOLEAN,
        );
    }
}

function instructions_get_content(): string
{
    /** @var mixed $raw */
    $raw = instructions_read_option_with_legacy(
        option_name: instructions_content_option_name(),
        legacy_option_name: instructions_legacy_content_option_name(),
        default: '',
    );
    return is_string($raw) ? $raw : '';
}

function instructions_custom_injection_suppression_state(string $action = 'read'): bool
{
    static $suppressed = false;

    $previous = $suppressed;
    if ($action === 'suppress') {
        $suppressed = true;
    }
    if ($action === 'restore_enabled') {
        $suppressed = true;
    }
    if ($action === 'restore_disabled') {
        $suppressed = false;
    }

    return $previous;
}

function instructions_custom_injection_suppressed(): bool
{
    return instructions_custom_injection_suppression_state();
}

function instructions_post_string(string $key): string
{
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw)) {
        return '';
    }

    return wp_unslash($raw);
}

function register_context_menu(): void
{
    if (!defined('VORMBYTE_VERSION')) {
        return;
    }

    // Stay out while a Vormbyte Pro that still manages custom instructions is
    // active: that Pro owns the instructions UI (its "Memory & Instructions"
    // page) until it is removed or updated to a version that hands context to
    // the base. The base then takes over automatically — see
    // legacy_pro_context_loaded().
    if (legacy_pro_context_loaded()) {
        return;
    }

    add_submenu_page(
        parent_slug: 'vormbyte-connect',
        page_title: __('Vormbyte Context', domain: 'vormbyte'),
        menu_title: __('Context', domain: 'vormbyte'),
        capability: \vormbyte_manage_capability(),
        menu_slug: context_page_slug(),
        callback: __NAMESPACE__ . '\\render_context_page',
    );
}

/** @return array{type:string,message:string}|null */
function context_handle_post(): ?array
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return null;
    }

    $action = instructions_post_string('vormbyte_context_action');
    if ($action !== 'save_context') {
        return null;
    }

    check_admin_referer('vormbyte_context');

    if (!\vormbyte_current_user_can_manage()) {
        wp_die(esc_html__('You are not allowed to manage context.', domain: 'vormbyte'));
    }

    return instructions_handle_save();
}

/** @return array{type:string,message:string} */
function instructions_handle_save(): array
{
    $content = instructions_post_string('instructions_content');
    instructions_update_content($content);

    return [
        'type' => 'success',
        'message' => __('User context saved.', domain: 'vormbyte'),
    ];
}

function context_system_instructions_preview(): string
{
    if (!function_exists('vormbyte_build_server_instructions')) {
        return __('System instructions are unavailable until the base Vormbyte plugin has loaded.', domain: 'vormbyte');
    }

    // In read mode agents receive the read-mode guidance alone, without the filtered additions.
    if (function_exists('vormbyte_is_read_mode') && \vormbyte_is_read_mode()) {
        return \vormbyte_build_server_instructions();
    }

    $previous = instructions_custom_injection_suppression_state(action: 'suppress');

    try {
        return (string) apply_filters(
            'vormbyte_discover_abilities_instructions',
            \vormbyte_build_server_instructions(),
        );
    } finally {
        instructions_custom_injection_suppression_state(action: $previous ? 'restore_enabled' : 'restore_disabled');
    }
}

function context_system_instructions_excerpt(string $instructions, int $line_count = 6): string
{
    if ($line_count < 1) {
        return '...';
    }

    $normalized = preg_replace(pattern: '/\r\n?/', replacement: "\n", subject: $instructions) ?? $instructions;
    $lines = explode(separator: "\n", string: ltrim($normalized, characters: "\n"));

    if (count($lines) <= $line_count) {
        return implode(separator: "\n", array: $lines);
    }

    $excerpt = array_slice($lines, offset: 0, length: $line_count);
    $excerpt[] = '...';

    return implode(separator: "\n", array: $excerpt);
}

function render_context_page(): void
{
    if (!\vormbyte_current_user_can_manage()) {
        return;
    }

    $notice = context_handle_post();

    if (function_exists('vormbyte_render_admin_header')) {
        vormbyte_render_admin_header();
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Context', domain: 'vormbyte'); ?></h1>
        <hr class="wp-header-end">

        <p class="description vormbyte-context-intro"><?php esc_html_e(
            'Review the system instructions connected agents receive, then add site-specific context that should apply in every conversation.',
            domain: 'vormbyte',
        ); ?></p>

        <?php render_context_notice($notice); ?>
        <?php render_context_styles(); ?>
        <?php render_context_system_section(); ?>
        <?php render_user_context_state(); ?>
        <?php do_action('vormbyte_context_page_sections'); ?>
    </div>
    <?php
}

function render_context_styles(): void
{ ?>
    <style>
        .vormbyte-context-intro { margin-top:8px; max-width:800px; }
        .vormbyte-context-section { margin-top:24px; max-width:1100px; }
        .vormbyte-context-section h2 { margin-bottom:4px; font-size:16px; }
        .vormbyte-context-panel { background:#fff; border:1px solid #dcdcde; border-radius:12px; padding:16px 20px; margin-top:12px; }
        .vormbyte-system-box summary { cursor:pointer; font-weight:600; }
        .vormbyte-system-preview-wrap { position:relative; margin-top:12px; }
        .vormbyte-system-preview { box-sizing:border-box; width:100%; max-height:none; overflow:auto; margin:0; padding:14px 16px; background:#f6f7f7; border:1px solid #dcdcde; border-radius:8px; white-space:pre-wrap; font-family:ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace; font-size:12px; line-height:1.55; }
        .vormbyte-system-preview.is-excerpt { margin-top:12px; }
        .vormbyte-system-details[open] + .vormbyte-system-preview.is-excerpt { display:none; }
        .vormbyte-context-guidance { margin-top:12px; color:#1d2327; }
        .vormbyte-context-guidance ul { list-style:disc; margin-left:20px; max-width:780px; }
        .vormbyte-context-guidance li { margin:4px 0; }
        .vormbyte-context-disabled { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; border-style:dashed; }
        .vormbyte-context-disabled p { margin:4px 0 0; color:#50575e; }
        .vormbyte-context-form { margin-top:12px; }
        .vormbyte-context-form textarea { font-family:ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace; font-size:13px; }
    </style>
    <?php }

function render_context_system_section(): void
{
    $preview = context_system_instructions_preview();
    $excerpt = context_system_instructions_excerpt($preview);
    ?>
    <section class="vormbyte-context-section" aria-labelledby="vormbyte-system-context-heading">
        <h2 id="vormbyte-system-context-heading"><?php esc_html_e('System context', domain: 'vormbyte'); ?></h2>
        <p class="description"><?php esc_html_e(
            'Read-only instructions Vormbyte generates for this site, adapting them to the plugins you have active. Shown here for visibility and not editable from this page.',
            domain: 'vormbyte',
        ); ?></p>

        <div class="vormbyte-context-panel vormbyte-system-box">
            <details class="vormbyte-system-details">
                <summary><?php esc_html_e('Show full system context', domain: 'vormbyte'); ?></summary>
                <div class="vormbyte-system-preview-wrap" aria-label="<?php echo
                    esc_attr__('Read-only system context preview', domain: 'vormbyte')
                ; ?>">
                    <pre class="vormbyte-system-preview is-full"><?php echo esc_html($preview); ?></pre>
                </div>
            </details>
            <pre class="vormbyte-system-preview is-excerpt"><?php echo esc_html($excerpt); ?></pre>
        </div>
    </section>
    <?php
}

function render_user_context_section(): void
{
    $content = instructions_get_content();
    $form_url = context_page_url();
    ?>
    <section class="vormbyte-context-section" aria-labelledby="vormbyte-user-context-heading">
        <h2 id="vormbyte-user-context-heading"><?php esc_html_e('User context', domain: 'vormbyte'); ?></h2>
        <p class="description"><?php esc_html_e(
            'Additional instructions provided by this site owner for all connected agents.',
            domain: 'vormbyte',
        ); ?></p>

        <div class="vormbyte-context-panel vormbyte-context-guidance">
            <p style="margin-top:0;"><?php esc_html_e(
                'Stable context agents should apply on this site without asking again.',
                domain: 'vormbyte',
            ); ?></p>
            <ul>
                <li><?php esc_html_e(
                    'Site goals, brand voice, audience, and naming conventions.',
                    domain: 'vormbyte',
                ); ?></li>
                <li><?php esc_html_e(
                    'Constraints: what to avoid, what needs approval, and preferred workflows.',
                    domain: 'vormbyte',
                ); ?></li>
            </ul>
            <p style="margin-bottom:0;"><?php esc_html_e(
                'No passwords, API keys, private data, or one-off notes. Keep it stable and site-wide.',
                domain: 'vormbyte',
            ); ?></p>
        </div>

        <form method="post" action="<?php echo esc_url($form_url); ?>" class="vormbyte-context-form">
            <?php wp_nonce_field('vormbyte_context'); ?>
            <input type="hidden" name="vormbyte_context_action" value="save_context">
            <label for="instructions_content" class="screen-reader-text"><?php esc_html_e(
                'User context',
                domain: 'vormbyte',
            ); ?></label>
            <textarea
                id="instructions_content"
                name="instructions_content"
                rows="14"
                class="large-text code"
                placeholder="<?php echo
                    esc_attr__(
                        'Write site-level context for connected agents. Markdown is supported.',
                        domain: 'vormbyte',
                    )
                ; ?>"
            ><?php echo esc_textarea($content); ?></textarea>
            <p style="margin-top:8px;">
                <button type="submit" class="button button-primary"><?php esc_html_e(
                    'Save context',
                    domain: 'vormbyte',
                ); ?></button>
            </p>
        </form>
    </section>
    <?php
}

function render_user_context_state(): void
{
    if (instructions_is_enabled()) {
        render_user_context_section();
        return;
    }

    $features_url = admin_url('admin.php?page=vormbyte-features#vormbyte-user-context');
    ?>
    <section class="vormbyte-context-section" aria-labelledby="vormbyte-user-context-disabled-heading">
        <h2 id="vormbyte-user-context-disabled-heading"><?php esc_html_e('User context', domain: 'vormbyte'); ?></h2>
        <div class="vormbyte-context-panel vormbyte-context-disabled">
            <div>
                <strong><?php esc_html_e('User context is disabled.', domain: 'vormbyte'); ?></strong>
                <p><?php esc_html_e(
                    'Connected agents receive only the system context shown above.',
                    domain: 'vormbyte',
                ); ?></p>
            </div>
            <a class="button button-secondary" href="<?php echo esc_url($features_url); ?>"><?php esc_html_e(
                'Manage features',
                domain: 'vormbyte',
            ); ?></a>
        </div>
    </section>
    <?php
}

/** @param array{type:string,message:string}|null $notice */
function render_context_notice(?array $notice): void
{
    if ($notice === null) {
        return;
    }

    $type = in_array($notice['type'], ['success', 'warning', 'error', 'info'], strict: true) ? $notice['type'] : 'info';
    ?>
    <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible"><p><?php echo
        esc_html($notice['message'])
    ; ?></p></div>
    <?php
}

function boot_context_admin(): void
{
    add_action('admin_menu', callback: __NAMESPACE__ . '\\register_context_menu', priority: 30);
}
