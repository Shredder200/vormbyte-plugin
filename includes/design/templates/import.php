<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use Vormbyte\Design\Admin;

if (!defined('ABSPATH')) {
    exit();
}

if (!Admin\current_user_can_manage()) {
    wp_die(__('You do not have permission to import designs.', domain: 'vormbyte'));
}

$gallery_url = admin_url('admin.php?page=' . Admin\PAGE_SLUG);
$action_url = admin_url('admin-post.php');
?>
<?php vormbyte_render_admin_header(
    logo_file: 'vormbyte-design-logo.svg',
    logo_alt: 'Vormbyte Design',
    logo_width: 340,
    logo_height: 40,
); ?>
<div class="wrap vormbyte-design">
    <a class="nd-detail-back" href="<?php echo esc_url($gallery_url); ?>">&larr; <?php esc_html_e(
        'All designs',
        domain: 'vormbyte',
    ); ?></a>

    <h1><?php esc_html_e('Import a DESIGN.md', domain: 'vormbyte'); ?></h1>
    <p class="vormbyte-design-intro"><?php esc_html_e(
        'Bring a design direction from another site or project. The name comes from the front matter or the first heading.',
        domain: 'vormbyte',
    ); ?></p>
    <p class="vormbyte-design-import-trust"><?php esc_html_e(
        'Your AI agent reads this DESIGN.md, and it has full access to this site. A file from a source you do not control could hide instructions meant to steer the agent beyond design work. Import only files you trust.',
        domain: 'vormbyte',
    ); ?></p>

    <form
        method="post"
        action="<?php echo esc_url($action_url); ?>"
        enctype="multipart/form-data"
        class="vormbyte-design-import"
        onsubmit="return confirm('<?php echo
            esc_js(__(
                'Your AI agent reads this DESIGN.md, and it has full access to this site. A file from a source you do not control could hide instructions meant to steer the agent beyond design work. Import only files you trust. Continue?',
                domain: 'vormbyte',
            ))
        ; ?>');"
    >
        <?php wp_nonce_field('vormbyte_design_import'); ?>
        <input type="hidden" name="action" value="vormbyte_design_import" />
        <p>
            <label><?php esc_html_e('Upload a .md file:', domain: 'vormbyte'); ?>
                <input type="file" name="design_file" accept=".md" />
            </label>
        </p>
        <p><?php esc_html_e('…or paste the DESIGN.md below:', domain: 'vormbyte'); ?></p>
        <p>
            <textarea
                name="design_content"
                rows="14"
                class="large-text code"
                placeholder="---&#10;name: My Design&#10;---&#10;## Overview&#10;…"
            ></textarea>
        </p>
        <p>
            <label><input type="checkbox" name="activate" value="1" />
                <?php esc_html_e('Activate after import', domain: 'vormbyte'); ?></label>
        </p>
        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e(
                'Import',
                domain: 'vormbyte',
            ); ?></button>
            <a href="<?php echo esc_url($gallery_url); ?>" class="button"><?php esc_html_e(
                'Cancel',
                domain: 'vormbyte',
            ); ?></a>
        </p>
    </form>
</div>
