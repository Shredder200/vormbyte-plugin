<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use Vormbyte\Skills\Admin;
use Vormbyte\Skills\Cpt;
use Vormbyte\Skills\Sources;

if (!defined('ABSPATH')) {
    exit();
}

if (!Admin\current_user_can_manage()) {
    wp_die(__('You do not have permission to view this page.', domain: 'vormbyte'));
}

$per_page = 50;
$paged = max(1, (int) ($_GET['paged'] ?? 1));
$user_query = new \WP_Query([
    'post_type' => Cpt\POST_TYPE,
    'post_status' => ['publish', 'draft'],
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
]);
/** @var list<\WP_Post> $user_posts */
$user_posts = $user_query->posts;
$user_total = (int) $user_query->found_posts;
$user_total_pages = (int) $user_query->max_num_pages;

// One-shot list of post IDs to flash-highlight (set by handle_upload).
$just_imported_key = 'vormbyte_skill_just_imported_' . get_current_user_id();
/** @var list<int> $just_imported */
$just_imported = [];
/** @var mixed $just_imported_raw */
$just_imported_raw = get_transient($just_imported_key);
if (is_array($just_imported_raw)) {
    /** @var mixed $post_id */
    foreach ($just_imported_raw as $post_id) {
        if (!is_scalar($post_id)) {
            continue;
        }
        $just_imported[] = (int) $post_id;
    }
}
if ($just_imported !== []) {
    delete_transient($just_imported_key);
}

/** @var list<\WP_Post> $trashed_posts */
$trashed_posts = get_posts([
    'post_type' => Cpt\POST_TYPE,
    'post_status' => 'trash',
    'posts_per_page' => -1,
    'orderby' => 'modified',
    'order' => 'DESC',
]);

// Group skills from non-user-cpt sources by source_id so each contributor
// gets its own table with its own dynamic heading (e.g. "Vormbyte Pro").
$external_groups = [];
foreach (Sources\registry() as $entry) {
    if ($entry['id'] === 'user-cpt') {
        continue;
    }
    $skills = $entry['loader']();
    if ($skills === []) {
        continue;
    }
    $external_groups[$entry['id']] = [
        'label' => $entry['label'],
        'skills' => $skills,
    ];
}

$action_url = admin_url('admin-post.php');
$new_url = add_query_arg(['page' => Admin\PAGE_SLUG, 'skill' => 'new'], admin_url('admin.php'));
?>
<?php vormbyte_render_admin_header(); ?>
<div class="wrap vormbyte-skills vormbyte-list-layout">
    <h1 class="wp-heading-inline"><?php esc_html_e('Skills', domain: 'vormbyte'); ?></h1>
    <label for="vormbyte-skills-upload-file" class="page-title-action"><?php esc_html_e(
        'Upload .md',
        domain: 'vormbyte',
    ); ?></label>
    <a href="<?php echo esc_url($new_url); ?>" class="page-title-action"><?php esc_html_e(
        'Add new',
        domain: 'vormbyte',
    ); ?></a>
    <?php if ($user_total > 0): ?>
        <a
            href="<?php echo
                esc_url(wp_nonce_url(add_query_arg([
                    'action' => 'vormbyte_skill_download_all',
                ], admin_url('admin-post.php')), action: 'vormbyte_skill_download_all'))
            ; ?>"
            class="page-title-action"
        ><?php esc_html_e('Download all', domain: 'vormbyte'); ?></a>
    <?php endif; ?>
    <hr class="wp-header-end" />

    <?php require __DIR__ . '/upload.php'; ?>

    <details class="vormbyte-skills-trust-warning">
        <summary>
            <span class="dashicons dashicons-shield" aria-hidden="true"></span>
            <span class="summary-text"><?php esc_html_e(
                'Only upload skills from sources you trust.',
                domain: 'vormbyte',
            ); ?></span>
            <span class="summary-toggle"><?php esc_html_e('Why?', domain: 'vormbyte'); ?></span>
        </summary>
        <div class="vormbyte-skills-trust-body">
            <p><?php esc_html_e(
                'A skill\'s description and body become part of the AI\'s context on this site. A malicious skill can:',
                domain: 'vormbyte',
            ); ?></p>
            <ul>
                <li><?php esc_html_e(
                    'Override or hijack the AI\'s behaviour with hidden instructions (prompt injection).',
                    domain: 'vormbyte',
                ); ?></li>
                <li><?php esc_html_e(
                    'Trick the AI into reading sensitive files (config, credentials, customer data) and sending them outside.',
                    domain: 'vormbyte',
                ); ?></li>
                <li><?php esc_html_e(
                    'Get the AI to run arbitrary code on your site via Vormbyte\'s PHP-execution abilities.',
                    domain: 'vormbyte',
                ); ?></li>
            </ul>
            <p><?php esc_html_e(
                'Treat an uploaded .md the same way you\'d treat installing a plugin: trust the author first.',
                domain: 'vormbyte',
            ); ?></p>
        </div>
    </details>

    <?php if ($user_posts === [] && $external_groups === []): ?>
        <div class="vormbyte-skills-empty">
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <p><?php esc_html_e(
                'No skills yet. Upload a .md file or create one from scratch.',
                domain: 'vormbyte',
            ); ?></p>
            <p>
                <a href="<?php echo esc_url($new_url); ?>" class="button button-primary"><?php esc_html_e(
                    'Create from scratch',
                    domain: 'vormbyte',
                ); ?></a>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($user_posts !== []): ?>
    <section class="vormbyte-admin-list-section">
        <div class="vormbyte-admin-list-header">
            <h2><?php esc_html_e('Your Skills', domain: 'vormbyte'); ?> <span class="count"><?php

            echo (int) $user_total;
            ?></span></h2>
            <input
                type="search"
                id="vormbyte-skills-search"
                class="vormbyte-skills-search"
                placeholder="<?php esc_attr_e('Filter on this page…', domain: 'vormbyte'); ?>"
                autocomplete="off"
            />
        </div>
        <div class="vormbyte-admin-list-list" data-vormbyte-skills-list>
            <?php foreach ($user_posts as $post):
                $slug = $post->post_name;
                $enabled = $post->post_status === 'publish';
                $description = trim($post->post_excerpt);
                $validation = Admin\validation_issues($post);
                $warnings = array_map(static fn(array $issue): string => $issue['label'], $validation['issues']);
                $has_warning = $warnings !== [];
                $critical_warning = $validation['critical'];
                $edit_url = add_query_arg([
                    'page' => Admin\PAGE_SLUG,
                    'skill' => $post->ID,
                ], admin_url('admin.php'));
                $row_classes = ['vormbyte-admin-list-row', 'vormbyte-list-row', 'is-user-skill'];
                $row_classes[] = $enabled ? 'is-on' : 'is-off';
                if ($has_warning) {
                    $row_classes[] = 'has-warn';
                }
                if (in_array((int) $post->ID, $just_imported, strict: true)) {
                    $row_classes[] = 'is-just-imported';
                }
                ?>
            <div
                class="<?php echo esc_attr(implode(' ', $row_classes)); ?>"
                data-vormbyte-search="<?php echo esc_attr($slug . ' ' . $description); ?>"
            >
                <a class="vormbyte-admin-list-main" href="<?php echo esc_url($edit_url); ?>">
                    <span class="slug"><?php echo esc_html($slug !== '' ? $slug : $post->post_title); ?></span>
                    <?php if (!$enabled): ?>
                        <span class="vormbyte-list-inline-state"><?php esc_html_e(
                            '— Disabled',
                            domain: 'vormbyte',
                        ); ?></span>
                    <?php endif; ?>
                    <?php if ($has_warning): ?>
                        <span
                            class="vormbyte-admin-list-warning<?php echo $critical_warning ? ' is-critical' : ''; ?>"
                            role="note"
                        ><span aria-hidden="true">⚠</span><span><?php echo
                            esc_html(implode(' · ', $warnings))
                        ; ?></span></span>
                    <?php endif; ?>
                </a>
                <div class="vormbyte-admin-list-actions vormbyte-list-actions vormbyte-list-progressive-actions">
                    <form method="post" action="<?php echo esc_url($action_url); ?>">
                        <?php wp_nonce_field('vormbyte_skill_toggle_status_' . $post->ID); ?>
                        <input type="hidden" name="action" value="vormbyte_skill_toggle_status" />
                        <input type="hidden" name="post_id" value="<?php echo (int) $post->ID; ?>" />
                        <button type="submit" class="action-btn"><?php echo
                            $enabled
                                ? esc_html__('Disable', domain: 'vormbyte')
                                : esc_html__('Enable', domain: 'vormbyte')
                        ; ?></button>
                    </form>
                    <a
                        class="action-btn"
                        href="<?php echo
                            esc_url(wp_nonce_url(
                                add_query_arg([
                                    'action' => 'vormbyte_skill_download',
                                    'post_id' => (int) $post->ID,
                                ], admin_url('admin-post.php')),
                                'vormbyte_skill_download_' . (int) $post->ID,
                            ))
                        ; ?>"
                    ><?php esc_html_e('Download', domain: 'vormbyte'); ?></a>
                    <form
                        method="post"
                        action="<?php echo esc_url($action_url); ?>"
                        onsubmit="return confirm('<?php echo
                            esc_js(__('Delete this skill permanently?', domain: 'vormbyte'))
                        ; ?>');"
                    >
                        <?php wp_nonce_field('vormbyte_skill_delete_' . $post->ID); ?>
                        <input type="hidden" name="action" value="vormbyte_skill_delete" />
                        <input type="hidden" name="post_id" value="<?php echo (int) $post->ID; ?>" />
                        <button type="submit" class="action-btn action-btn--danger"><?php

                        esc_html_e('Delete', domain: 'vormbyte');
                        ?></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($user_total_pages > 1): ?>
            <nav class="vormbyte-skills-pagination" aria-label="<?php esc_attr_e(
                'Skills pagination',
                domain: 'vormbyte',
            ); ?>">
                <?php

                /** @var mixed $links */
                $links = paginate_links([
                    'base' => add_query_arg(['paged' => '%#%'], admin_url('admin.php?page=' . Admin\PAGE_SLUG)),
                    'format' => '',
                    'current' => $paged,
                    'total' => $user_total_pages,
                    'prev_text' => '‹',
                    'next_text' => '›',
                    'type' => 'plain',
                ]);
                echo is_string($links) ? $links : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </nav>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($trashed_posts !== []): ?>
    <section class="vormbyte-admin-list-section vormbyte-admin-list-section--trash">
        <div class="vormbyte-admin-list-header">
            <h2><?php esc_html_e('Trash', domain: 'vormbyte'); ?> <span class="count"><?php

            echo (int) count($trashed_posts);
            ?></span></h2>
            <span class="vormbyte-admin-list-trash-hint"><?php esc_html_e(
                'Trashed skills are not loaded by the AI. Items are auto-removed after 30 days.',
                domain: 'vormbyte',
            ); ?></span>
        </div>
        <div class="vormbyte-admin-list-list">
            <?php foreach ($trashed_posts as $post):
                $slug = $post->post_name !== '' ? $post->post_name : $post->post_title;
                // WP appends `__trashed` to post_name when trashing; strip for display.
                $slug = (string) preg_replace('/__trashed$/', replacement: '', subject: $slug);
                ?>
            <div class="vormbyte-admin-list-row vormbyte-list-row is-trashed">
                <div class="vormbyte-admin-list-main vormbyte-admin-list-main--trash" tabindex="0">
                    <span class="slug"><?php echo esc_html($slug); ?></span>
                </div>
                <div class="vormbyte-admin-list-actions vormbyte-list-actions vormbyte-list-progressive-actions">
                    <form method="post" action="<?php echo esc_url($action_url); ?>">
                        <?php wp_nonce_field('vormbyte_skill_restore_' . $post->ID); ?>
                        <input type="hidden" name="action" value="vormbyte_skill_restore" />
                        <input type="hidden" name="post_id" value="<?php echo (int) $post->ID; ?>" />
                        <button type="submit" class="action-btn"><?php

                        esc_html_e('Restore', domain: 'vormbyte');
                        ?></button>
                    </form>
                    <form
                        method="post"
                        action="<?php echo esc_url($action_url); ?>"
                        onsubmit="return confirm('<?php echo
                            esc_js(__('Delete this skill permanently? This cannot be undone.', domain: 'vormbyte'))
                        ; ?>');"
                    >
                        <?php wp_nonce_field('vormbyte_skill_permanent_delete_' . $post->ID); ?>
                        <input type="hidden" name="action" value="vormbyte_skill_permanent_delete" />
                        <input type="hidden" name="post_id" value="<?php echo (int) $post->ID; ?>" />
                        <button type="submit" class="action-btn action-btn--danger"><?php

                        esc_html_e('Delete permanently', domain: 'vormbyte');
                        ?></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php foreach ($external_groups as $source_id => $group): ?>
    <section class="vormbyte-admin-list-section vormbyte-admin-list-section--external">
        <div class="vormbyte-admin-list-header">
            <h2><?php

            printf(
                /* translators: %s: contributor label, e.g. "Vormbyte Pro" */
                esc_html__('Skills from %s', domain: 'vormbyte'),
                esc_html($group['label']),
            );
            ?> <span class="count"><?php

            echo (int) count($group['skills']);
            ?></span></h2>
        </div>
        <p class="vormbyte-admin-list-source-blurb">
            <?php if (str_starts_with($source_id, 'vormbyte-pro')): ?>
                <?php esc_html_e(
                    'These skills are enabled or disabled with their complete Vormbyte Pro specialization. You see only the skills relevant to the plugins you have installed.',
                    domain: 'vormbyte',
                ); ?>
            <?php endif; ?>
            <?php if (!str_starts_with($source_id, 'vormbyte-pro')): ?>
                <?php esc_html_e(
                    'Feature skills are enabled or disabled with their complete Vormbyte feature.',
                    domain: 'vormbyte',
                ); ?>
            <?php endif; ?>
        </p>
        <div class="vormbyte-admin-list-list">
            <?php foreach ($group['skills'] as $skill):
                $slug = (string) ($skill['slug'] ?? '');
                $description = trim((string) ($skill['description'] ?? ''));
                $missing_description = $description === '';
                $features = \Vormbyte\Features\features();
                $managers = $features->features_for_skill($slug);
                $toggleable_managers = array_values(array_filter(
                    $managers,
                    static fn(\Vormbyte\Features\Definition $feature): bool => $feature->toggleable,
                ));
                $feature_managed = $toggleable_managers !== [];
                $infrastructure = $managers !== [] && $toggleable_managers === [];
                $active = $features->is_skill_active($slug);
                $specialization_managers = array_values(array_filter(
                    $toggleable_managers,
                    static fn(\Vormbyte\Features\Definition $feature): bool => $feature->kind === 'specialization',
                ));
                $only_specializations =
                    $feature_managed && count($specialization_managers) === count($toggleable_managers);
                $only_features = $feature_managed && $specialization_managers === [];
                $manager_label = implode(', ', array_map(
                    static fn(\Vormbyte\Features\Definition $feature): string => $feature->label(),
                    $toggleable_managers,
                ));
                $manager_count = count($toggleable_managers);
                $manage_label = match (true) {
                    $only_specializations && $manager_count === 1 => sprintf(
                        /* translators: %s: Vormbyte Pro specialization name */
                        __('Part of Vormbyte Pro specialization: %s →', domain: 'vormbyte'),
                        $manager_label,
                    ),
                    $only_specializations => __('Part of multiple Vormbyte Pro specializations →', domain: 'vormbyte'),
                    $only_features && $manager_count === 1 => sprintf(
                        /* translators: %s: Vormbyte feature name */
                        __('Part of Vormbyte feature: %s →', domain: 'vormbyte'),
                        $manager_label,
                    ),
                    $only_features => __('Part of multiple Vormbyte features →', domain: 'vormbyte'),
                    default => __('Part of multiple Vormbyte components →', domain: 'vormbyte'),
                };
                ?>
            <div class="vormbyte-admin-list-row vormbyte-list-row is-external <?php echo
                $active ? 'is-on' : 'is-off'
            ; ?>">
                <?php if (!$missing_description): ?>
                    <details class="vormbyte-admin-list-main vormbyte-admin-list-main--external">
                        <summary class="vormbyte-admin-list-summary">
                            <span class="slug"><?php echo esc_html($slug); ?></span>
                            <?php if (!$active): ?>
                                <span class="vormbyte-list-inline-state"><?php esc_html_e(
                                    '— Disabled',
                                    domain: 'vormbyte',
                                ); ?></span>
                            <?php endif; ?>
                        </summary>
                        <div class="vormbyte-admin-list-detail">
                            <p><?php echo esc_html($description); ?></p>
                        </div>
                    </details>
                <?php endif; ?>
                <?php if ($missing_description): ?>
                    <div class="vormbyte-admin-list-main vormbyte-admin-list-main--external" tabindex="0">
                        <span class="slug"><?php echo esc_html($slug); ?></span>
                        <?php if (!$active): ?>
                            <span class="vormbyte-list-inline-state"><?php esc_html_e(
                                '— Disabled',
                                domain: 'vormbyte',
                            ); ?></span>
                        <?php endif; ?>
                        <span
                            class="vormbyte-admin-list-warning is-critical"
                            role="note"
                        ><span aria-hidden="true">⚠</span><span><?php esc_html_e(
                            'Missing description',
                            domain: 'vormbyte',
                        ); ?></span></span>
                    </div>
                <?php endif; ?>
                <div class="vormbyte-admin-list-actions vormbyte-list-actions vormbyte-list-progressive-actions">
                    <?php if ($feature_managed): ?>
                        <a class="action-btn" href="<?php echo
                            esc_url(\Vormbyte\Features\Admin\url(
                                count($toggleable_managers) === 1 ? $toggleable_managers[0]->id : null,
                            ))
                        ; ?>"><?php

                        echo esc_html($manage_label);
                        ?></a>
                    <?php endif; ?>
                    <?php if ($infrastructure): ?>
                        <span class="vormbyte-admin-list-action-note"><?php esc_html_e(
                            'Required by Vormbyte',
                            domain: 'vormbyte',
                        ); ?></span>
                    <?php endif; ?>
                    <?php if (!$feature_managed && !$infrastructure): ?>
                        <span class="vormbyte-admin-list-action-note"><?php esc_html_e(
                            'Managed by Vormbyte',
                            domain: 'vormbyte',
                        ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

</div>
<script>
(function () {
    var file = document.getElementById('vormbyte-skills-upload-file');
    var form = document.getElementById('vormbyte-skills-upload-form');
    var MAX_UPLOAD = 20;
    if (file && form) {
        file.addEventListener('change', function () {
            if (!file.files || file.files.length === 0) {
                return;
            }
            if (file.files.length > MAX_UPLOAD) {
                alert('Too many files. Upload up to ' + MAX_UPLOAD + ' skills at a time.');
                file.value = '';
                return;
            }
            form.submit();
        });
    }

    // Client-side filter for the current page's rows. Descriptions stay
    // searchable even though the compact list keeps them out of the scan view.
    var search = document.getElementById('vormbyte-skills-search');
    var list = document.querySelector('[data-vormbyte-skills-list]');
    if (search && list) {
        var rows = Array.prototype.slice.call(list.querySelectorAll('.vormbyte-admin-list-row'));
        search.addEventListener('input', function () {
            var q = search.value.toLowerCase().trim();
            rows.forEach(function (row) {
                if (q === '') {
                    row.style.display = '';
                    return;
                }
                var searchable = row.getAttribute('data-vormbyte-search') || row.textContent || '';
                row.style.display = searchable.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
})();
</script>
