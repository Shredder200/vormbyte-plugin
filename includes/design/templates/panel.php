<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use Vormbyte\Design\Admin;
use Vormbyte\Design\Contract;
use Vormbyte\Design\Library;
use Vormbyte\Design\Store;
use Vormbyte\Design\Tokens;

if (!defined('ABSPATH')) {
    exit();
}

if (!Admin\current_user_can_manage()) {
    wp_die(__('You do not have permission to view this page.', domain: 'vormbyte'));
}

$library = Library\all();
$active_slug = Store\get_active_slug();
$action_url = admin_url('admin-post.php');

// The page chrome quietly wears the active design's accent colour.
$active = $active_slug !== '' ? Library\find($active_slug) : null;
$page_accent = $active !== null ? Tokens\css_vars(Tokens\extract($active['content']))['--nd-accent'] ?? '' : '';
$page_style = $page_accent !== '' ? '--ds-accent:' . $page_accent : '';
?>
<?php vormbyte_render_admin_header(
    logo_file: 'vormbyte-design-logo.svg',
    logo_alt: 'Vormbyte Design',
    logo_width: 340,
    logo_height: 40,
); ?>
<div class="wrap vormbyte-design" style="<?php echo esc_attr($page_style); ?>">
    <h1 class="wp-heading-inline"><?php esc_html_e('Design', domain: 'vormbyte'); ?></h1>
    <a href="<?php echo
        esc_url(add_query_arg(['page' => Admin\PAGE_SLUG, 'import' => 1], admin_url('admin.php')))
    ; ?>" class="page-title-action"><?php esc_html_e('Import DESIGN.md', domain: 'vormbyte'); ?></a>
    <hr class="wp-header-end" />
    <p class="vormbyte-design-intro"><?php esc_html_e(
        'Your site has one design direction: the active design your AI builds within. It is created for this site from a brief, not picked from a catalog. On every page the AI also enforces a floor of anti-slop rules.',
        domain: 'vormbyte',
    ); ?></p>

    <?php if ($library === []): ?>
        <div class="vormbyte-design-empty">
            <p><?php esc_html_e(
                'No design system yet. Ask your AI agent to create one for this site, or import a DESIGN.md.',
                domain: 'vormbyte',
            ); ?></p>
        </div>
    <?php endif; ?>

    <div class="vormbyte-design-gallery">
        <?php foreach ($library as $entry):
            $tokens = Tokens\extract($entry['content']);
            $inspection = Contract\inspect($entry['content']);
            $is_ready = $inspection['readiness']['ready'];
            $vars_style = Tokens\css_vars_string($tokens);
            $accent = Tokens\css_vars($tokens)['--nd-accent'] ?? '';
            $is_active = $entry['slug'] === $active_slug;

            $edit_url = '';
            $post = Store\find_user_post($entry['slug']);
            if ($post instanceof \WP_Post) {
                $edit_url = add_query_arg([
                    'page' => Admin\PAGE_SLUG,
                    'design' => $post->ID,
                ], admin_url('admin.php'));
            }
            $card_style = $accent !== '' ? '--ds-accent:' . $accent : '';
            $view_url = add_query_arg(['page' => Admin\PAGE_SLUG, 'view' => $entry['slug']], admin_url('admin.php'));
            ?>
            <article class="vormbyte-design-card2<?php echo $is_active ? ' is-active' : ''; ?>" style="<?php echo
                esc_attr($card_style)
            ; ?>">
                <a class="vormbyte-design-stage" href="<?php echo esc_url($view_url); ?>">
                    <?php require __DIR__ . '/preview.php'; ?>
                </a>
                <div class="vormbyte-design-card2-body">
                    <div class="vormbyte-design-card2-head">
                        <h2><a href="<?php echo esc_url($view_url); ?>"><?php echo esc_html($entry['name']); ?></a></h2>
                        <?php if ($is_active): ?>
                            <span class="vormbyte-design-active-badge"><?php esc_html_e(
                                'Active',
                                domain: 'vormbyte',
                            ); ?></span>
                        <?php endif; ?>
                        <?php if (!$is_ready): ?>
                            <span class="vormbyte-design-incomplete-badge"><?php esc_html_e(
                                'Incomplete',
                                domain: 'vormbyte',
                            ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($entry['description'] !== ''): ?>
                        <p class="vormbyte-design-desc"><?php echo esc_html($entry['description']); ?></p>
                    <?php endif; ?>
                    <?php $waivers = Vormbyte\Design\Preflight\waivers($entry['content']); ?>
                    <?php if ($waivers !== []): ?>
                        <span class="vormbyte-design-allows"><?php echo
                            esc_html(sprintf(
                                /* translators: %s: list of anti-slop rules this design waives */
                                __('Allows: %s', domain: 'vormbyte'),
                                implode(' · ', $waivers),
                            ))
                        ; ?><span class="vormbyte-design-allows-help" title="<?php echo
                            esc_attr__(
                                'Anti-slop rules this design intentionally waives. Vormbyte normally flags these AI tells; here they count as a deliberate house-style choice, not a mistake.',
                                domain: 'vormbyte',
                            )
                        ; ?>">?</span></span>
                    <?php endif; ?>
                    <div class="vormbyte-design-card2-actions">
                        <?php if (!$is_active && $is_ready): ?>
                            <form method="post" action="<?php echo esc_url($action_url); ?>">
                                <?php wp_nonce_field('vormbyte_design_activate'); ?>
                                <input type="hidden" name="action" value="vormbyte_design_activate" />
                                <input type="hidden" name="slug" value="<?php echo esc_attr($entry['slug']); ?>" />
                                <button type="submit" class="button button-primary"><?php esc_html_e(
                                    'Activate',
                                    domain: 'vormbyte',
                                ); ?></button>
                            </form>
                        <?php endif; ?>
                        <?php if (!$is_active && !$is_ready): ?>
                            <button type="button" class="button" disabled title="<?php echo
                                esc_attr(Contract\activation_error($inspection))
                            ; ?>"><?php esc_html_e('Activate', domain: 'vormbyte'); ?></button>
                        <?php endif; ?>
                        <?php if ($edit_url !== ''): ?>
                            <a class="button-link" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e(
                                'Edit',
                                domain: 'vormbyte',
                            ); ?></a>
                        <?php endif; ?>
                        <form method="post" action="<?php echo esc_url($action_url); ?>">
                            <?php wp_nonce_field('vormbyte_design_duplicate'); ?>
                            <input type="hidden" name="action" value="vormbyte_design_duplicate" />
                            <input type="hidden" name="slug" value="<?php echo esc_attr($entry['slug']); ?>" />
                            <button type="submit" class="button-link"><?php esc_html_e(
                                'Duplicate',
                                domain: 'vormbyte',
                            ); ?></button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
