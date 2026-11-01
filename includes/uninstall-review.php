<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

const VORMBYTE_UNINSTALL_PLAN_OPTION = 'vormbyte_uninstall_plan';

const VORMBYTE_UNINSTALL_PAGE_SLUG = 'vormbyte-uninstall';

add_action('admin_menu', callback: 'vormbyte_register_uninstall_review_page');
add_action('network_admin_menu', callback: 'vormbyte_register_uninstall_review_page');
add_action('admin_post_vormbyte_prepare_uninstall', callback: 'vormbyte_handle_prepare_uninstall');

$vormbyte_plugin_basename = plugin_basename(dirname(__DIR__) . '/vormbyte.php');
add_filter('plugin_action_links_' . $vormbyte_plugin_basename, callback: 'vormbyte_uninstall_action_links');
add_filter(
    'network_admin_plugin_action_links_' . $vormbyte_plugin_basename,
    callback: 'vormbyte_uninstall_action_links',
);

function vormbyte_clear_uninstall_plan(): void
{
    delete_site_option(VORMBYTE_UNINSTALL_PLAN_OPTION);
}

function vormbyte_register_uninstall_review_page(): void
{
    vormbyte_add_hidden_admin_page(
        __('Before deactivating Vormbyte', domain: 'vormbyte'),
        VORMBYTE_UNINSTALL_PAGE_SLUG,
        callback: 'vormbyte_render_uninstall_review_page',
    );
}

/**
 * @param array<string, string> $actions Plugin row actions.
 * @return array<string, string>
 */
function vormbyte_uninstall_action_links(array $actions): array
{
    if (!array_key_exists('deactivate', $actions) || !vormbyte_current_user_can_manage()) {
        return $actions;
    }

    $url = is_network_admin()
        ? network_admin_url('admin.php?page=' . VORMBYTE_UNINSTALL_PAGE_SLUG)
        : admin_url('admin.php?page=' . VORMBYTE_UNINSTALL_PAGE_SLUG);
    $actions['deactivate'] = sprintf(
        '<a href="%1$s" aria-label="%2$s">%3$s</a>',
        esc_url($url),
        esc_attr__('Review options before deactivating Vormbyte', domain: 'vormbyte'),
        esc_html__('Deactivate', domain: 'vormbyte'),
    );

    return $actions;
}

function vormbyte_render_uninstall_review_page(): void
{
    if (!vormbyte_current_user_can_manage()) {
        wp_die(esc_html__('You are not allowed to deactivate Vormbyte.', domain: 'vormbyte'), args: [
            'response' => 403,
        ]);
    }

    $sandbox_file_count = vormbyte_uninstall_sandbox_file_count();
    $password_count = vormbyte_uninstall_application_password_count();
    $content_counts = vormbyte_uninstall_content_counts();
    vormbyte_render_admin_header();
    ?>
    <div class="wrap vormbyte-uninstall-wrap">
        <h1><?php esc_html_e('Before deactivating Vormbyte', domain: 'vormbyte'); ?></h1>
        <p><?php esc_html_e(
            'Choose what Vormbyte should remove if you delete the plugin later.',
            domain: 'vormbyte',
        ); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vormbyte_prepare_uninstall">
            <?php wp_nonce_field('vormbyte_prepare_uninstall'); ?>

        <?php if ($sandbox_file_count > 0): ?>
            <div class="card" style="max-width:760px; margin-top:20px;">
                <h2><?php esc_html_e('Sandbox files', domain: 'vormbyte'); ?></h2>
                <p><strong><?php echo
                    esc_html(sprintf(
                        _n(
                            single: '%d file is present in the Vormbyte sandbox.',
                            plural: '%d files are present in the Vormbyte sandbox.',
                            number: $sandbox_file_count,
                            domain: 'vormbyte',
                        ),
                        $sandbox_file_count,
                    ))
                ; ?></strong></p>
                <p><?php esc_html_e(
                    'These files can contain PHP code that provides active site functionality. Deactivating Vormbyte stops loading them, which may break the site or cause fatal errors. Move any functionality you need to a persistent, independently loaded location before continuing.',
                    domain: 'vormbyte',
                ); ?></p>
                <p><code style="overflow-wrap:anywhere;"><?php echo esc_html(vormbyte_get_sandbox_dir()); ?></code></p>
                <details>
                    <summary style="cursor:pointer;">
                        <strong><?php esc_html_e('Ask your AI client how to move them', domain: 'vormbyte'); ?></strong>
                    </summary>
                    <p><?php esc_html_e(
                        'Use Vormbyte itself to prepare the move. Copy this prompt into your connected AI client:',
                        domain: 'vormbyte',
                    ); ?></p>
                    <textarea id="vormbyte-sandbox-prompt" class="large-text code" rows="6" readonly><?php esc_html_e(
                        'I am going to delete the Vormbyte plugin and need to move the files currently in its sandbox to persistent storage first. Use Vormbyte itself to inspect the sandbox files and determine which persistent locations are available on this WordPress site. Do not deactivate, delete, or uninstall Vormbyte, because that would end this connection. Explain the available options and their trade-offs. Do not move any files until I choose an option.',
                        domain: 'vormbyte',
                    ); ?></textarea>
                    <p>
                        <button type="button" class="button" id="vormbyte-copy-sandbox-prompt">
                            <?php esc_html_e('Copy prompt', domain: 'vormbyte'); ?>
                        </button>
                        <span id="vormbyte-copy-sandbox-status" aria-live="polite"></span>
                    </p>
                </details>
                <p>
                    <label>
                        <input type="checkbox" name="confirm_sandbox_files" value="1" required>
                        <strong><?php esc_html_e(
                            'I understand that these sandbox files may power site functionality and that deactivating Vormbyte could break the site or cause fatal errors.',
                            domain: 'vormbyte',
                        ); ?></strong>
                    </label>
                </p>
            </div>
        <?php endif; ?>

            <div class="card" style="max-width:760px; margin-top:20px;">
                <h2><?php esc_html_e('Stored content', domain: 'vormbyte'); ?></h2>
                <p><?php esc_html_e(
                    'Deactivating Vormbyte keeps this content in the database, but Memory, user-added Skills, and Chat will be unavailable. They become available again when Vormbyte and any required add-on are active.',
                    domain: 'vormbyte',
                ); ?></p>

                <?php if ($content_counts['memories'] > 0): ?>
                    <p><strong><?php echo
                        esc_html(sprintf(
                            _n(
                                single: '%d Vormbyte Pro memory was found.',
                                plural: '%d Vormbyte Pro memories were found.',
                                number: $content_counts['memories'],
                                domain: 'vormbyte',
                            ),
                            $content_counts['memories'],
                        ))
                    ; ?></strong></p>
                    <p>
                        <label>
                            <input type="checkbox" name="delete_memories" value="1">
                            <?php esc_html_e(
                                'When Vormbyte is deleted, permanently delete these memories',
                                domain: 'vormbyte',
                            ); ?>
                        </label>
                    </p>
                <?php endif; ?>
                <?php if ($content_counts['memories'] === 0): ?>
                    <p><?php esc_html_e('No Vormbyte Pro memories were found.', domain: 'vormbyte'); ?></p>
                <?php endif; ?>

                <?php if ($content_counts['user_skills'] > 0): ?>
                    <p><strong><?php echo
                        esc_html(sprintf(
                            _n(
                                single: '%d user-added skill was found.',
                                plural: '%d user-added skills were found.',
                                number: $content_counts['user_skills'],
                                domain: 'vormbyte',
                            ),
                            $content_counts['user_skills'],
                        ))
                    ; ?></strong></p>
                    <p>
                        <label>
                            <input type="checkbox" name="delete_user_skills" value="1">
                            <?php esc_html_e(
                                'When Vormbyte is deleted, permanently delete these user-added skills',
                                domain: 'vormbyte',
                            ); ?>
                        </label>
                    </p>
                <?php endif; ?>
                <?php if ($content_counts['user_skills'] === 0): ?>
                    <p><?php esc_html_e('No user-added skills were found.', domain: 'vormbyte'); ?></p>
                <?php endif; ?>

                <?php if ($content_counts['chat_sessions'] > 0): ?>
                    <p><strong><?php echo
                        esc_html(sprintf(
                            _n(
                                single: '%d Vormbyte Chat session was found.',
                                plural: '%d Vormbyte Chat sessions were found.',
                                number: $content_counts['chat_sessions'],
                                domain: 'vormbyte',
                            ),
                            $content_counts['chat_sessions'],
                        ))
                    ; ?></strong></p>
                    <p>
                        <label>
                            <input type="checkbox" name="delete_chat_sessions" value="1" checked>
                            <?php esc_html_e(
                                'When Vormbyte is deleted, permanently delete these chat sessions',
                                domain: 'vormbyte',
                            ); ?>
                        </label>
                    </p>
                <?php endif; ?>
                <?php if ($content_counts['chat_sessions'] === 0): ?>
                    <p><?php esc_html_e('No Vormbyte Chat sessions were found.', domain: 'vormbyte'); ?></p>
                <?php endif; ?>
            </div>

            <div class="card" style="max-width:760px; margin-top:20px;">
                <h2><?php esc_html_e('Connections', domain: 'vormbyte'); ?></h2>
                <p>
                    <label>
                        <input type="checkbox" name="delete_oauth" value="1" checked>
                        <?php esc_html_e(
                            'When Vormbyte is deleted, delete OAuth connections and authorization data',
                            domain: 'vormbyte',
                        ); ?>
                    </label>
                </p>
                <?php if ($password_count > 0): ?>
                    <p>
                        <label>
                            <input type="checkbox" name="delete_application_passwords" value="1" checked>
                            <?php echo
                                esc_html(sprintf(
                                    _n(
                                        single: 'When Vormbyte is deleted, revoke %d Vormbyte Application Password across all users',
                                        plural: 'When Vormbyte is deleted, revoke %d Vormbyte Application Passwords across all users',
                                        number: $password_count,
                                        domain: 'vormbyte',
                                    ),
                                    $password_count,
                                ))
                            ; ?>
                        </label>
                    </p>
                <?php endif; ?>
                <?php if ($password_count === 0): ?>
                    <p><?php esc_html_e('No Vormbyte Application Passwords were found.', domain: 'vormbyte'); ?></p>
                <?php endif; ?>
            </div>

            <?php if (get_site_option('vormbyte_ghost_mode', default_value: false) !== false): ?>
                <div class="card" style="max-width:760px; margin-top:20px;">
                    <h2><?php esc_html_e('Ghost Mode', domain: 'vormbyte'); ?></h2>
                    <p>
                        <label>
                            <input type="checkbox" name="delete_ghost_mode" value="1">
                            <?php esc_html_e(
                                'When Vormbyte is deleted, remove Ghost Mode settings',
                                domain: 'vormbyte',
                            ); ?>
                        </label>
                    </p>
                    <p class="description"><?php esc_html_e(
                        'Kept by default, so reinstalling Vormbyte restores the same Ghost Mode.',
                        domain: 'vormbyte',
                    ); ?></p>
                </div>
            <?php endif; ?>

            <?php submit_button(__('Save choices and deactivate Vormbyte', domain: 'vormbyte')); ?>
        </form>
        <script>
            (() => {
                const button = document.getElementById('vormbyte-copy-sandbox-prompt');
                const prompt = document.getElementById('vormbyte-sandbox-prompt');
                const status = document.getElementById('vormbyte-copy-sandbox-status');
                if (!button || !prompt || !status) {
                    return;
                }

                button.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(prompt.value);
                        status.textContent = ' <?php echo esc_js(__('Copied.', domain: 'vormbyte')); ?>';
                    } catch {
                        prompt.select();
                        status.textContent = ' <?php echo
                            esc_js(__('Select the prompt and copy it manually.', domain: 'vormbyte'))
                        ; ?>';
                    }
                });
            })();
        </script>
    </div>
    <?php
}

function vormbyte_handle_prepare_uninstall(): void
{
    if (!vormbyte_current_user_can_manage()) {
        wp_die(esc_html__('You are not allowed to deactivate Vormbyte.', domain: 'vormbyte'), args: [
            'response' => 403,
        ]);
    }

    $plugin = plugin_basename(dirname(__DIR__) . '/vormbyte.php');
    if (!current_user_can('deactivate_plugin', $plugin)) {
        wp_die(esc_html__('You are not allowed to deactivate Vormbyte.', domain: 'vormbyte'), args: [
            'response' => 403,
        ]);
    }

    check_admin_referer('vormbyte_prepare_uninstall');
    if (vormbyte_uninstall_sandbox_file_count() > 0 && !array_key_exists('confirm_sandbox_files', $_POST)) {
        wp_die(
            esc_html__('Confirm that you understand what will happen to the sandbox files.', domain: 'vormbyte'),
            args: ['response' => 400],
        );
    }
    update_site_option(VORMBYTE_UNINSTALL_PLAN_OPTION, [
        'delete_oauth' => array_key_exists('delete_oauth', $_POST),
        'delete_application_passwords' => array_key_exists('delete_application_passwords', $_POST),
        'delete_memories' => array_key_exists('delete_memories', $_POST),
        'delete_user_skills' => array_key_exists('delete_user_skills', $_POST),
        'delete_chat_sessions' => array_key_exists('delete_chat_sessions', $_POST),
        'delete_ghost_mode' => array_key_exists('delete_ghost_mode', $_POST),
    ]);

    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $network_wide = is_multisite() && is_plugin_active_for_network($plugin);
    deactivate_plugins($plugin, silent: false, network_wide: $network_wide);

    $plugins_url = $network_wide
        ? network_admin_url('plugins.php?deactivate=true')
        : admin_url('plugins.php?deactivate=true');
    wp_safe_redirect($plugins_url);
    exit();
}

function vormbyte_uninstall_sandbox_file_count(): int
{
    $sandbox_dir = vormbyte_get_sandbox_dir();
    if (!is_dir($sandbox_dir)) {
        return 0;
    }

    try {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $sandbox_dir,
            FilesystemIterator::SKIP_DOTS,
        ));
    } catch (UnexpectedValueException) {
        return 0;
    }

    $count = 0;
    foreach ($files as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }
        if (vormbyte_is_sandbox_internal_file_name($file->getFilename())) {
            continue;
        }
        $count++;
    }

    return $count;
}

/** @return array{memories: int, user_skills: int, chat_sessions: int} */
function vormbyte_uninstall_content_counts(): array
{
    $plugin = plugin_basename(dirname(__DIR__) . '/vormbyte.php');
    if (!is_multisite() || !is_plugin_active_for_network($plugin)) {
        return vormbyte_uninstall_current_site_content_counts();
    }

    $counts = ['memories' => 0, 'user_skills' => 0, 'chat_sessions' => 0];
    // @mago-expect analysis:mixed-assignment -- WordPress returns site ids when fields=ids.
    $site_ids = get_sites(['fields' => 'ids', 'number' => 0]);
    if (!is_array($site_ids)) {
        return $counts;
    }

    // @mago-expect analysis:mixed-assignment
    foreach ($site_ids as $site_id) {
        switch_to_blog((int) $site_id);
        $site_counts = vormbyte_uninstall_current_site_content_counts();
        restore_current_blog();

        $counts['memories'] += $site_counts['memories'];
        $counts['user_skills'] += $site_counts['user_skills'];
        $counts['chat_sessions'] += $site_counts['chat_sessions'];
    }

    return $counts;
}

/** @return array{memories: int, user_skills: int, chat_sessions: int} */
function vormbyte_uninstall_current_site_content_counts(): array
{
    return [
        'memories' => vormbyte_uninstall_post_type_count('vormbyte_memory'),
        'user_skills' => vormbyte_uninstall_post_type_count('vormbyte_skill'),
        'chat_sessions' => vormbyte_uninstall_chat_session_count(),
    ];
}

function vormbyte_uninstall_post_type_count(string $post_type): int
{
    // @mago-expect lint:no-global -- $wpdb is WordPress' database handle.
    global $wpdb;

    /** @var wpdb $wpdb */
    $query = $wpdb->prepare('SELECT COUNT(ID) FROM %i WHERE post_type = %s', $wpdb->posts, $post_type);
    if (!is_string($query)) {
        return 0;
    }

    return (int) $wpdb->get_var($query);
}

function vormbyte_uninstall_chat_session_count(): int
{
    // @mago-expect lint:no-global -- $wpdb is WordPress' database handle.
    global $wpdb;

    /** @var wpdb $wpdb */
    $table = $wpdb->prefix . 'vormbyte_chat_sessions';
    $table_query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table));
    // MySQL with lower_case_table_names=1 (the Windows default) reports the name lowercased.
    if (!is_string($table_query) || strcasecmp((string) $wpdb->get_var($table_query), $table) !== 0) {
        return 0;
    }

    $count_query = $wpdb->prepare('SELECT COUNT(*) FROM %i', $table);
    return is_string($count_query) ? (int) $wpdb->get_var($count_query) : 0;
}

function vormbyte_uninstall_application_password_count(): int
{
    $count = 0;
    foreach (vormbyte_application_password_user_ids() as $user_id) {
        foreach (WP_Application_Passwords::get_user_application_passwords($user_id) as $password) {
            if (!vormbyte_is_application_password($password)) {
                continue;
            }
            $count++;
        }
    }

    return $count;
}

/** @return list<int> */
function vormbyte_application_password_user_ids(): array
{
    // @mago-expect lint:no-global -- $wpdb is WordPress' database handle.
    global $wpdb;

    /** @var wpdb $wpdb */
    // @mago-expect analysis:possibly-invalid-argument -- $wpdb->usermeta is WordPress' users table.
    $query = $wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
        WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS,
    );
    if (!is_string($query)) {
        return [];
    }
    $user_ids = $wpdb->get_col($query);

    return array_values(array_unique(array_map('intval', $user_ids)));
}
