<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\GhostMode;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

const PAGE_SLUG = 'vormbyte-ghost-mode';

const DISABLE_ACTION = 'vormbyte_ghost_mode_disable';

const PANEL_HOOK = 'vormbyte_ghost_mode_panel';

function page_url(): string
{
    return admin_url('admin.php?page=' . PAGE_SLUG);
}

function register_menu(): void
{
    add_submenu_page(
        parent_slug: 'vormbyte-connect',
        page_title: __('Ghost Mode', domain: 'vormbyte'),
        menu_title: __('Ghost Mode', domain: 'vormbyte'),
        capability: \vormbyte_manage_capability(),
        menu_slug: PAGE_SLUG,
        callback: __NAMESPACE__ . '\\render_page',
    );
}

function enqueue_page_assets(string $hook): void
{
    if ($hook !== 'vormbyte_page_' . PAGE_SLUG) {
        return;
    }

    $path = __DIR__ . '/assets/page.css';
    $version = defined('WP_DEBUG') && constant('WP_DEBUG') === true && is_file($path)
        ? (string) filemtime($path)
        : VORMBYTE_VERSION;

    wp_enqueue_style(
        'vormbyte-ghost-mode',
        (string) VORMBYTE_PLUGIN_URL . 'includes/ghost-mode/assets/page.css',
        [],
        $version,
    );
}

function render_page(): void
{
    if (!\vormbyte_current_user_can_manage() || !ui_visible()) {
        wp_die(esc_html__('Sorry, you are not allowed to access this page.', domain: 'default'), args: [
            'response' => 403,
        ]);
    }
    if (has_action(PANEL_HOOK)) {
        do_action(PANEL_HOOK);

        return;
    }

    $status = status();
    \vormbyte_render_admin_header();
    ?>
    <div class="wrap vormbyte-ghost-mode">
        <h1 class="wp-heading-inline"><?php esc_html_e('Ghost Mode', domain: 'vormbyte'); ?></h1>
        <hr class="wp-header-end">
        <p class="vormbyte-gm-lead"><?php esc_html_e(
            'Show Vormbyte only to the administrators you choose.',
            domain: 'vormbyte',
        ); ?></p>
        <?php render_disabled_notice(); ?>
        <?php

        match (true) {
            $status['state'] === 'off' => render_presentation(),
            default => render_status($status),
        };
        ?>
    </div>
    <?php
}

function render_disabled_notice(): void
{
    if (($_GET['vormbyte_ghost_mode'] ?? null) !== 'disabled') {
        return;
    }
    wp_admin_notice(esc_html__('Ghost Mode is off. Every administrator sees Vormbyte again.', domain: 'vormbyte'), [
        'type' => 'success',
    ]);
}

function render_presentation(): void
{ ?>
    <div class="vormbyte-gm-compare">
        <div class="vormbyte-gm-card vormbyte-gm-team">
            <h2><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e(
                'Administrators you choose',
                domain: 'vormbyte',
            ); ?></h2>
            <ul class="vormbyte-gm-list">
                <li><?php esc_html_e('The Vormbyte menu and admin bar entry', domain: 'vormbyte'); ?></li>
                <li><?php esc_html_e('Vormbyte notices', domain: 'vormbyte'); ?></li>
                <li><?php esc_html_e('Vormbyte in Plugins and Updates', domain: 'vormbyte'); ?></li>
            </ul>
        </div>
        <div class="vormbyte-gm-card vormbyte-gm-client">
            <h2><span class="dashicons dashicons-hidden" aria-hidden="true"></span><?php esc_html_e(
                'Other administrators',
                domain: 'vormbyte',
            ); ?></h2>
            <ul class="vormbyte-gm-list">
                <li><?php esc_html_e('No Vormbyte menu or admin bar entry', domain: 'vormbyte'); ?></li>
                <li><?php esc_html_e('No Vormbyte notices', domain: 'vormbyte'); ?></li>
                <li><?php esc_html_e('No Vormbyte rows in Plugins and Updates', domain: 'vormbyte'); ?></li>
            </ul>
        </div>
    </div>
    <?php render_limits(); ?>
    <?php if (\vormbyte_pro_is_active()): ?>
        <div class="vormbyte-gm-card vormbyte-gm-pro">
            <p><?php esc_html_e(
                'Ghost Mode needs a newer version of Vormbyte Pro. Update Vormbyte Pro to turn it on.',
                domain: 'vormbyte',
            ); ?></p>
            <a href="<?php echo
                esc_url(admin_url('update-core.php'))
            ; ?>" class="button button-primary"><?php esc_html_e('Go to Updates', domain: 'vormbyte'); ?></a>
        </div>
        <?php return;
    endif; ?>
    <div class="vormbyte-gm-card vormbyte-gm-pro">
        <p><?php esc_html_e(
            'Ghost Mode is turned on by a Vormbyte Pro add-on, which this edition does not include.',
            domain: 'vormbyte',
        ); ?></p>
    </div>
    <?php }

function render_security_callout(): void
{ ?>
    <div class="vormbyte-gm-security" role="note">
        <p><strong><?php esc_html_e(
            'Ghost Mode is not a security feature.',
            domain: 'vormbyte',
        ); ?></strong> <?php esc_html_e(
            'It hides the Vormbyte menu, its admin bar entry, its notices and its rows in the Plugins and Updates screens, but it changes what other administrators see, not what they can do: they can still find Vormbyte, connect an AI to it, or keep the connection they already have.',
            domain: 'vormbyte',
        ); ?></p>
    </div>
    <?php }

function render_limits(): void
{
    render_security_callout(); ?>
    <div class="vormbyte-gm-card vormbyte-gm-keypoints">
        <p><strong><?php esc_html_e('Updates are up to you.', domain: 'vormbyte'); ?></strong> <?php esc_html_e(
            'Administrators not listed under Visible to do not see Vormbyte updates, so keeping Vormbyte up to date is the responsibility of the administrators who do.',
            domain: 'vormbyte',
        ); ?></p>
    </div>
    <?php
}

/**
 * @param array{state: 'off'|'on'|'unreadable', bypass: bool, owners: list<int>, ignored_ids: list<int>, malformed: int, fail_closed: bool, updated_at: int, updated_by: int, source: string} $status
 */
function render_status(array $status): void
{
    $alert = $status['state'] === 'unreadable' || $status['fail_closed'];
    ?>
    <div class="vormbyte-gm-card">
        <div class="vormbyte-gm-status-head">
            <h2><?php esc_html_e('Status', domain: 'vormbyte'); ?></h2>
            <span class="vormbyte-gm-pill <?php echo $alert ? 'is-alert' : 'is-on'; ?>"><?php echo
                $alert ? esc_html__('Needs attention', domain: 'vormbyte') : esc_html__('On', domain: 'vormbyte')
            ; ?></span>
        </div>
        <?php if ($status['state'] !== 'unreadable'): ?>
            <p class="vormbyte-gm-label"><?php esc_html_e('Visible to', domain: 'vormbyte'); ?></p>
            <ul class="vormbyte-gm-chips">
                <?php foreach ($status['owners'] as $owner_id): ?>
                    <li><?php echo esc_html(describe_user($owner_id)); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php render_status_details($status); ?>
        <p><?php echo
            \vormbyte_pro_is_active()
                ? esc_html__('Update Vormbyte Pro to change who sees Vormbyte.', domain: 'vormbyte')
                : esc_html__('To change who sees Vormbyte, activate Vormbyte Pro.', domain: 'vormbyte')
        ; ?></p>
    </div>
    <?php render_limits(); ?>
    <div class="vormbyte-gm-card vormbyte-gm-off">
        <h2><?php esc_html_e('Turn off Ghost Mode', domain: 'vormbyte'); ?></h2>
        <?php render_disable_form(); ?>
    </div>
    <?php
}

/**
 * @param array{state: 'off'|'on'|'unreadable', bypass: bool, owners: list<int>, ignored_ids: list<int>, malformed: int, fail_closed: bool, updated_at: int, updated_by: int, source: string} $status
 */
function render_status_details(array $status): void
{
    $ignored = count($status['ignored_ids']) + $status['malformed'];
    ?>
    <?php if ($status['state'] === 'unreadable'): ?>
        <p class="vormbyte-gm-warning is-error"><?php esc_html_e(
            'The Ghost Mode settings cannot be read, so Vormbyte is hidden from every administrator.',
            domain: 'vormbyte',
        ); ?></p>
    <?php endif; ?>
    <?php if ($status['state'] !== 'unreadable' && $status['fail_closed']): ?>
        <p class="vormbyte-gm-warning is-error"><?php esc_html_e(
            'No administrator on the list can manage Vormbyte, so it is hidden from everyone.',
            domain: 'vormbyte',
        ); ?></p>
    <?php endif; ?>
    <?php if ($ignored > 0): ?>
        <p class="vormbyte-gm-warning"><?php echo
            esc_html(sprintf(
                _n(
                    single: '%d entry in the list is not an administrator who can manage Vormbyte and is ignored.',
                    plural: '%d entries in the list are not administrators who can manage Vormbyte and are ignored.',
                    number: $ignored,
                    domain: 'vormbyte',
                ),
                $ignored,
            ))
        ; ?></p>
    <?php endif; ?>
    <?php if ($status['bypass']): ?>
        <p class="vormbyte-gm-warning"><?php esc_html_e(
            'VORMBYTE_GHOST_MODE_BYPASS is set in wp-config.php, so every administrator sees Vormbyte right now.',
            domain: 'vormbyte',
        ); ?></p>
    <?php endif; ?>
    <?php
}

function render_disable_form(): void
{ ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr(DISABLE_ACTION); ?>">
        <?php wp_nonce_field(DISABLE_ACTION); ?>
        <label>
            <input type="checkbox" name="confirm" value="1" required>
            <?php esc_html_e('I understand that every administrator will see Vormbyte again.', domain: 'vormbyte'); ?>
        </label>
        <?php submit_button(
            __('Turn off Ghost Mode', domain: 'vormbyte'),
            type: 'secondary',
            name: 'submit',
            wrap: false,
        ); ?>
    </form>
    <?php }

function handle_disable(): void
{
    if (!\vormbyte_current_user_can_manage() || !ui_visible()) {
        wp_die(esc_html__('Sorry, you are not allowed to access this page.', domain: 'default'), args: [
            'response' => 403,
        ]);
    }
    check_admin_referer(DISABLE_ACTION);
    if (!array_key_exists('confirm', $_POST)) {
        wp_die(esc_html__('Confirm that every administrator will see Vormbyte again.', domain: 'vormbyte'), args: [
            'response' => 400,
        ]);
    }

    $error = disable(get_current_user_id(), \vormbyte_pro_is_active() ? 'pro' : 'free-ui');
    if ($error instanceof WP_Error) {
        wp_die(esc_html($error->get_error_message()), args: ['response' => 500]);
    }

    $query = ['vormbyte_ghost_mode' => 'disabled'];
    wp_safe_redirect(add_query_arg($query, page_url()));
    exit();
}

function describe_user(int $user_id): string
{
    $user = get_userdata($user_id);
    if ($user === false) {
        /* translators: %d: user ID. */
        return sprintf(__('User #%d', domain: 'vormbyte'), $user_id);
    }

    if ($user->display_name === '' || $user->display_name === $user->user_login) {
        return $user->user_login;
    }

    return sprintf('%s (%s)', $user->display_name, $user->user_login);
}
