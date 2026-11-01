<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use Vormbyte\Design\Admin;
use Vormbyte\Design\Cpt;
use Vormbyte\Design\Tokens;

if (!defined('ABSPATH')) {
    exit();
}

if (!Admin\current_user_can_manage()) {
    wp_die(__('You do not have permission to edit designs.', domain: 'vormbyte'));
}

$design_param = $_GET['design'] ?? '';
$post_id = is_scalar($design_param) ? (int) $design_param : 0;
// @mago-expect analysis:mixed-assignment
$maybe_post = $post_id > 0 ? get_post($post_id) : null;
if (!$maybe_post instanceof \WP_Post || $maybe_post->post_type !== Cpt\POST_TYPE) {
    wp_die(__('Design not found.', domain: 'vormbyte'));
}
/** @var \WP_Post $post */
$post = $maybe_post;
$content = $post->post_content;
$name = $post->post_title !== '' ? $post->post_title : $post->post_name;

$list_url = admin_url('admin.php?page=' . Admin\PAGE_SLUG);
$action_url = admin_url('admin-post.php');
$tokens = Tokens\extract($content);
$vars_style = Tokens\css_vars_string($tokens);
?>
<?php vormbyte_render_admin_header(
    logo_file: 'vormbyte-design-logo.svg',
    logo_alt: 'Vormbyte Design',
    logo_width: 340,
    logo_height: 40,
); ?>
<div class="wrap vormbyte-design vormbyte-design-edit">
    <h1>
        <a href="<?php echo esc_url($list_url); ?>">← <?php esc_html_e('Design', domain: 'vormbyte'); ?></a>
        / <?php echo esc_html($name); ?>
    </h1>

    <div class="vormbyte-design-edit-grid">
        <form method="post" action="<?php echo esc_url($action_url); ?>" class="vormbyte-design-edit-form">
            <?php wp_nonce_field('vormbyte_design_save_' . $post_id); ?>
            <input type="hidden" name="action" value="vormbyte_design_save" />
            <input type="hidden" name="design_id" value="<?php echo (int) $post_id; ?>" />
            <p class="description"><?php esc_html_e(
                'Edit the raw DESIGN.md. The name comes from the front matter. The preview updates as you type.',
                domain: 'vormbyte',
            ); ?></p>
            <textarea name="content" id="vormbyte-design-content" rows="22" class="large-text code"><?php

            echo esc_textarea($content);
            ?></textarea>
            <p>
                <button type="submit" class="button button-primary"><?php esc_html_e(
                    'Save',
                    domain: 'vormbyte',
                ); ?></button>
                <a href="<?php echo esc_url($list_url); ?>" class="button"><?php esc_html_e(
                    'Cancel',
                    domain: 'vormbyte',
                ); ?></a>
            </p>
        </form>

        <div class="vormbyte-design-edit-preview">
            <h2><?php esc_html_e('Live preview', domain: 'vormbyte'); ?></h2>
            <?php require __DIR__ . '/preview.php'; ?>
        </div>
    </div>

    <form method="post" action="<?php echo esc_url($action_url); ?>" class="vormbyte-design-delete-form"
        onsubmit="return confirm('<?php echo esc_js(__('Delete this design permanently?', domain: 'vormbyte')); ?>');">
        <?php wp_nonce_field('vormbyte_design_delete_' . $post_id); ?>
        <input type="hidden" name="action" value="vormbyte_design_delete" />
        <input type="hidden" name="design_id" value="<?php echo (int) $post_id; ?>" />
        <button type="submit" class="button button-link-delete"><?php esc_html_e(
            'Delete design',
            domain: 'vormbyte',
        ); ?></button>
    </form>
</div>
