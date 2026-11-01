<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Child-process runner for ReadModePolicyTest: php read-mode-policy-runner.php <plugin root> <scenario>.
 */

namespace Vormbyte\Features {
    /** Every Ability is active and standalone, so only the hub rules and read mode decide. */
    function features(): object
    {
        return new class {
            public function is_ability_active(string $ability_name, string $category = ''): bool
            {
                return true;
            }

            public function feature_for_ability(string $ability_name, string $category = ''): ?object
            {
                return null;
            }
        };
    }
}

namespace Vormbyte\Skills\Sources {
    /** No skill source knows the requested slug, so skill-get falls back to the previous owner. */
    function find(string $slug): ?array
    {
        return null;
    }

    /** A skill source, recording every time it is consulted (which runs the sources' loaders). */
    function discoverable(string $mode): array
    {
        $GLOBALS['skill_source_loads'][] = $mode;
        return [[
            'slug' => 'demo-skill',
            'name' => 'Demo Skill',
            'description' => 'A demo skill.',
            'content' => 'Demo skill body.',
            'source' => 'test',
            'enable_prompt' => true,
            'enable_agentic' => true,
        ]];
    }
}

namespace {
    if ($argc !== 3) {
        exit(2);
    }

    [, $root, $scenario] = $argv;

    require __DIR__ . '/read-mode-stubs.php';
    require __DIR__ . '/options-wpdb.php';
    require $root . '/includes/helpers.php';
    require $root . '/includes/read-mode/policy.php';

    if (!in_array($scenario, ['no-pro', 'hooks', 'annotations', 'policy-broken-filter', 'policy-frozen'], true)) {
        test_install_pro_stand_in();
    }

    const TEST_ALL_ABILITIES = [
        'core/get-environment-info',
        'core/get-site-info',
        'vormbyte-mcp-adapter/discover-abilities',
        'vormbyte-mcp-adapter/get-ability-info',
        'vormbyte-mcp-adapter/execute-ability',
        'vormbyte/activate-design',
        'vormbyte/agent-context',
        'vormbyte/check-design',
        'vormbyte/create-admin-access-link',
        'vormbyte/create-upload-link',
        'vormbyte/delete-design',
        'vormbyte/delete-file',
        'vormbyte/disable-file',
        'vormbyte/edit-file',
        'vormbyte/enable-file',
        'vormbyte/execute-php',
        'vormbyte/get-active-design',
        'vormbyte/get-design',
        'vormbyte/get-wp-cli-job',
        'vormbyte/gutenberg-add-pending-change',
        'vormbyte/gutenberg-create-pending-batch',
        'vormbyte/gutenberg-delete-pending-batch',
        'vormbyte/gutenberg-delete-pending-change',
        'vormbyte/gutenberg-enable-batch-finalization',
        'vormbyte/gutenberg-get-block-type',
        'vormbyte/gutenberg-get-content',
        'vormbyte/gutenberg-get-finalization-url',
        'vormbyte/gutenberg-get-finalizer-runtime',
        'vormbyte/gutenberg-get-pending-batch',
        'vormbyte/gutenberg-list-block-types',
        'vormbyte/gutenberg-list-pending-batches',
        'vormbyte/gutenberg-write-content',
        'vormbyte/list-design-library',
        'vormbyte/list-directory',
        'vormbyte/read-file',
        'vormbyte/run-wp-cli',
        'vormbyte/save-design',
        'vormbyte/skill-delete',
        'vormbyte/skill-edit',
        'vormbyte/skill-get',
        'vormbyte/skill-prompt-skill-creator',
        'vormbyte/skill-write',
        'vormbyte/write-file',
        'vormbyte/get-object',
        'vormbyte/query-objects',
        'vormbyte/get-options',
        'vormbyte/list-options',
        'vormbyte/describe-schema',
        'vormbyte/read-query',
    ];

    /** The WordPress pieces the server instructions read, defined once per process. */
    function test_define_environment(): void
    {
        if (class_exists('WP_User', false)) {
            return;
        }
        class WP_User
        {
            public int $ID = 7;
        }
        class WP_Theme
        {
            public function get(string $key): string
            {
                return 'Twenty Test';
            }

            public function get_template(): string
            {
                return 'twentytest';
            }

            public function get_stylesheet(): string
            {
                return 'twentytest';
            }
        }
        function wp_get_current_user(): WP_User
        {
            return new WP_User();
        }
        function get_bloginfo(string $show = ''): string
        {
            return '6.9.4';
        }
        function get_locale(): string
        {
            return 'en_US';
        }
        function wp_get_theme(): WP_Theme
        {
            return new WP_Theme();
        }
    }

    function test_register_all(): void
    {
        foreach (TEST_ALL_ABILITIES as $name) {
            test_register($name, meta: ['mcp' => ['public' => true, 'type' => 'tool']]);
        }
        // Annotated read-only by its provider, which must not be enough to survive read mode.
        test_register('acme/delete-everything', 'acme', [
            'mcp' => ['public' => true],
            'annotations' => ['readonly' => true, 'destructive' => false],
        ]);
        test_register('acme/report', 'acme', ['mcp' => ['public' => true]]);
    }

    function test_policy_result(): array
    {
        vormbyte_apply_ability_policy();
        return ['registered' => array_keys(wp_get_abilities()), 'unregistered' => $GLOBALS['unregistered']];
    }

    $GLOBALS['options']['vormbyte_ai_abilities_enabled'] = '1';
    $GLOBALS['options']['vormbyte_ai_abilities_domain'] = 'example.test';

    $result = match ($scenario) {
        'policy-off' => (static function (): array {
            $GLOBALS['options']['vormbyte_ability_rules'] = ['acme/report' => ['disabled' => true]];
            test_register_all();
            return test_policy_result();
        })(),
        'policy-on' => (static function (): array {
            test_read_mode(true);
            test_register_all();
            return test_policy_result() + ['allowed' => vormbyte_read_mode_allowed_abilities()];
        })(),
        'policy-on-with-rule' => (static function (): array {
            test_read_mode(true);
            // One of the abilities Pro adds is one the administrator chooses; the other is required.
            add_filter('vormbyte_read_mode_abilities', static function (array $abilities): array {
                $abilities['vormbyte/get-object']['optional'] = true;
                return $abilities;
            }, 20);
            $GLOBALS['options']['vormbyte_ability_rules'] = [
                'vormbyte/get-object' => ['disabled' => true],
                'vormbyte/read-query' => ['disabled' => true],
            ];
            test_register_all();
            return test_policy_result();
        })(),
        'policy-screen-off' => (static function (): array {
            $GLOBALS['is_admin'] = true;
            $_GET['page'] = 'vormbyte-abilities';
            $GLOBALS['options']['vormbyte_ability_rules'] = ['acme/report' => ['disabled' => true]];
            test_register_all();
            return test_policy_result();
        })(),
        'policy-screen-on' => (static function (): array {
            $GLOBALS['is_admin'] = true;
            $_GET['page'] = 'vormbyte-features';
            test_read_mode(true);
            $GLOBALS['options']['vormbyte_ability_rules'] = ['vormbyte/read-query' => ['disabled' => true]];
            test_register_all();
            return test_policy_result();
        })(),
        'policy-filter' => (static function (): array {
            test_read_mode(true);
            // The single filter adds an ability with its callbacks, removes one, and carries entries that are not usable.
            add_filter('vormbyte_read_mode_abilities', static function (array $abilities): array {
                unset($abilities['vormbyte/read-query']);
                $abilities['acme/report'] = [
                    'execute' => 'test_pro_read_execute',
                    'permission' => 'vormbyte_permission_callback',
                    'guidance' => "A report.\n<b>Two</b> lines.",
                ];
                $abilities['vormbyte/execute-php'] = ['execute' => 'vormbyte_execute_php'];
                $abilities['acme/no-callbacks'] = 'not an entry';
                $abilities[42] = ['execute' => 'a', 'permission' => 'b'];

                return $abilities;
            });
            test_register_all();
            return test_policy_result() + [
                'allowed' => vormbyte_read_mode_allowed_abilities(),
                'instructions' => vormbyte_read_mode_instruction_lines(),
            ];
        })(),
        'declared-on', 'declared-off' => (static function () use ($scenario): array {
            test_read_mode(true);
            if ($scenario === 'declared-on') {
                add_filter('vormbyte_read_mode_allow_declared_abilities', static fn(): bool => true);
            }
            add_filter('wp_register_ability_args', 'vormbyte_read_mode_guard_ability_args', PHP_INT_MAX, 2);
            test_register_all();

            $register = static function (string $name, ?bool $readonly) : void {
                wp_register_ability($name, [
                    'description' => 'About ' . $name . '.',
                    'permission_callback' => static fn(): bool => true,
                    'execute_callback' => static fn(): array => [],
                    'meta' => [
                        'mcp' => ['public' => true, 'type' => 'tool'],
                        'annotations' => $readonly === null ? [] : ['readonly' => $readonly],
                    ],
                ]);
            };
            $register('acme/declared-read', true);
            $register('acme/declared-write', false);
            $register('acme/not-declared', null);
            // Vormbyte's own namespaces never count as declared, whatever the annotation says.
            $register('vormbyte/look-alike', true);
            $register('vormbyte-extra/look-alike', true);

            $guard = static function (string $name): ?bool {
                $ability = wp_get_ability($name);
                $callbacks = $ability === null ? null : vormbyte_read_mode_ability_callbacks($ability);
                $permission = $callbacks['permission'] ?? null;

                return is_callable($permission) ? (bool) $permission() : null;
            };
            $names = ['acme/declared-read', 'acme/declared-write', 'acme/not-declared', 'vormbyte/look-alike'];
            $before = [];
            foreach ($names as $name) {
                $before[$name] = $guard($name);
            }
            $result = test_policy_result();
            $after = [];
            foreach ($names as $name) {
                $after[$name] = $guard($name);
            }

            return $result + [
                'allowed' => vormbyte_read_mode_allowed_abilities(),
                'allows' => [
                    'acme/declared-read' => vormbyte_read_mode_allows_ability('acme/declared-read'),
                    'acme/declared-write' => vormbyte_read_mode_allows_ability('acme/declared-write'),
                    'vormbyte/look-alike' => vormbyte_read_mode_allows_ability('vormbyte/look-alike'),
                ],
                'declared' => vormbyte_read_mode_declared_abilities(),
                'candidates' => array_keys(vormbyte_read_mode_declared_candidates()),
                'guard_before' => $before,
                'guard_after' => $after,
            ];
        })(),
        'policy-broken-filter' => (static function (): array {
            test_read_mode(true);
            add_filter('vormbyte_read_mode_abilities', static fn(): string => 'not a list');
            test_register_all();
            return test_policy_result() + ['allowed' => vormbyte_read_mode_allowed_abilities()];
        })(),
        'policy-frozen' => (static function (): array {
            test_read_mode(true);
            function did_action(string $hook): int
            {
                return 1;
            }
            function doing_action(string $hook): bool
            {
                return false;
            }
            $before = vormbyte_read_mode_allowed_abilities();
            // A filter attached after the first check, once all plugins have loaded, has no effect.
            add_filter('vormbyte_read_mode_abilities', static function (array $abilities): array {
                $abilities['acme/late'] = ['execute' => 'late_execute', 'permission' => 'late_permission'];
                return $abilities;
            });
            return ['before' => $before, 'after' => vormbyte_read_mode_allowed_abilities()];
        })(),
        'no-pro' => (static function (): array {
            test_read_mode(true);
            test_register_all();
            return test_policy_result() + [
                'allowed' => vormbyte_read_mode_allowed_abilities(),
                'instructions' => vormbyte_read_mode_instruction_lines(),
            ];
        })(),
        'mcp-tools' => (static function (): array {
            test_register_all();
            // The adapter's own meta-tools carry no MCP metadata.
            test_register('vormbyte-mcp-adapter/get-ability-info', 'vormbyte-mcp-adapter');
            test_register('vormbyte/list-design-library', 'design-system', ['mcp' => ['public' => true, 'type' => 'tool']]);
            test_register('vormbyte/skill-get', 'skill', ['mcp' => ['public' => true, 'type' => 'prompt']]);
            unset($GLOBALS['abilities']['vormbyte/describe-schema']);
            $trio = [
                'vormbyte-mcp-adapter/discover-abilities',
                'vormbyte-mcp-adapter/get-ability-info',
                'vormbyte-mcp-adapter/execute-ability',
            ];
            $discover = static fn(string $type): array => ['discovered/' . $type];
            $off = vormbyte_mcp_server_components($trio, $discover);
            $off_config = vormbyte_read_mode_default_server_config(['tools' => $trio, 'server_id' => 'x']);
            // Blocked public resource and prompt Abilities that are still registered.
            test_register('acme/secret-resource', 'acme', ['mcp' => ['public' => true, 'type' => 'resource']]);
            test_register('acme/write-prompt', 'acme', ['mcp' => ['public' => true, 'type' => 'prompt']]);
            test_read_mode(true);
            add_filter('vormbyte_read_mode_abilities', static function (array $abilities): array {
                $abilities['acme/allowed-prompt'] = [
                    'execute' => 'test_pro_read_execute',
                    'permission' => 'vormbyte_permission_callback',
                ];
                return $abilities;
            });
            test_register('acme/allowed-prompt', 'acme', ['mcp' => ['public' => true, 'type' => 'prompt']]);
            return [
                'off' => $off,
                'off_config' => $off_config,
                'on' => vormbyte_mcp_server_components($trio, $discover),
                'on_config' => vormbyte_read_mode_default_server_config([
                    'tools' => $trio,
                    'resources' => ['acme/secret-resource'],
                    'prompts' => ['acme/write-prompt'],
                    'server_id' => 'x',
                ]),
            ];
        })(),
        'errors' => (static function (): array {
            $off = vormbyte_read_mode_unavailable_ability_error('vormbyte/execute-php');
            test_read_mode(true);
            return [
                'off' => $off,
                'blocked' => test_export(vormbyte_read_mode_unavailable_ability_error('vormbyte/execute-php')),
                'allowed' => vormbyte_read_mode_unavailable_ability_error('vormbyte/read-query'),
                'empty' => vormbyte_read_mode_unavailable_ability_error(''),
                'route' => test_export(vormbyte_read_mode_blocked_error('uploading files through an upload link')),
                'instructions' => vormbyte_read_mode_instruction_lines(),
            ];
        })(),
        'agent-surfaces-on', 'agent-surfaces-off' => (static function () use ($root, $scenario): array {
            test_define_environment();
            $GLOBALS['skill_source_loads'] = [];
            if ($scenario === 'agent-surfaces-on') {
                test_read_mode(true);
            }
            function vormbyte_server_compatibility(): array
            {
                return ['plugin_version' => 'test'];
            }
            // What the skill catalog, Pro's memory preview and license notice, and the administrator's Context add.
            add_filter(
                'vormbyte_discover_abilities_instructions',
                static fn(string $instructions): string => $instructions . "\n\nINJECTED: skill catalog, Pro memory preview.",
            );
            add_filter(
                'vormbyte_visual_backend_discovery_instructions',
                static fn(string $instructions): string => $instructions . "\n\nINJECTED: Visual extension guidance.",
            );

            require $root . '/vendor/vormbyte/mcp-adapter/autoload.php';
            require $root . '/includes/ability-metadata.php';
            require $root . '/includes/abilities/agent-context.php';
            require $root . '/includes/abilities/discover-abilities.php';
            require $root . '/includes/chat.php';
            require $root . '/vormbyte-visual/includes/BackendTools.php';
            require $root . '/includes/instructions-admin.php';
            require $root . '/includes/skills/parser.php';
            require $root . '/includes/skills/prompts.php';

            $context = vormbyte_build_agent_context();
            \Vormbyte\Skills\Prompts\register_dynamic_abilities();

            return [
                'agent_context_description' => $GLOBALS['abilities']['vormbyte/agent-context']->get_description(),
                'agent_context_instructions' => $context['instructions'],
                'agent_context_skills' => $context['skills'],
                'agent_context_read_mode' => $context['read_mode'],
                'discover_instructions' => vormbyte_execute_discover_abilities()['vormbyte_instructions'],
                'chat_instructions' => vormbyte_chat_server_instructions(),
                'visual_instructions' => \Vormbyte\Visual\BackendTools\discovery_instructions(),
                'admin_preview' => \Vormbyte\Context\context_system_instructions_preview(),
                'skill_prompts' => array_values(array_filter(
                    array_keys($GLOBALS['abilities']),
                    static fn(string $name): bool => str_starts_with($name, 'vormbyte/skill-prompt-'),
                )),
                'skill_source_loads' => $GLOBALS['skill_source_loads'],
                'read_mode_instructions' => vormbyte_read_mode_instructions(),
            ];
        })(),
        'instructions' => (static function (): array {
            test_define_environment();
            $off = vormbyte_build_server_instructions();
            test_read_mode(true);
            return ['off' => $off, 'on' => vormbyte_build_server_instructions()];
        })(),
        'hooks' => (static function (): array {
            vormbyte_register_read_mode_hooks();
            return array_map(
                static fn(array $hook): array => [$hook[0], is_string($hook[1]) ? $hook[1] : 'closure', $hook[2]],
                $GLOBALS['hooks'],
            );
        })(),
        'annotations' => (static function () use ($root): array {
            test_read_mode(true);
            require $root . '/vendor/vormbyte/mcp-adapter/autoload.php';
            vormbyte_register_read_mode_hooks();
            \Vormbyte\Vendor\WP\MCP\Abilities\GetAbilityInfoAbility::register();
            \Vormbyte\Vendor\WP\MCP\Abilities\ExecuteAbilityAbility::register();
            require $root . '/includes/abilities/read-file.php';
            require $root . '/includes/abilities/list-directory.php';
            require $root . '/includes/abilities/agent-context.php';
            require $root . '/includes/abilities/discover-abilities.php';

            $abilities = [];
            foreach (vormbyte_read_mode_allowed_abilities() as $name) {
                $ability = wp_get_ability($name);
                $args = $ability instanceof WP_Ability ? $ability->get_args() : null;
                $permission = $args['permission_callback'] ?? null;
                if ($permission instanceof \Vormbyte\ReadMode\PermissionGuard) {
                    $permission = $permission->wrapped_callback();
                }
                $abilities[$name] = $args === null ? null : [
                    'annotations' => $ability->get_meta()['annotations'] ?? null,
                    'permission_callback' => is_string($permission) ? $permission : 'callable',
                    'category' => $ability->get_category(),
                    // Registered from the real files, through the guard: the trusted map must match them.
                    'trusted' => vormbyte_read_mode_trusts_ability($ability),
                    'guarded' => ($args['permission_callback'] ?? null) instanceof \Vormbyte\ReadMode\PermissionGuard,
                ];
            }

            return ['abilities' => $abilities, 'categories' => array_keys($GLOBALS['ability_categories'])];
        })(),
        'skill-get-previous-on', 'skill-get-previous-off' => (static function () use ($root, $scenario): array {
            if ($scenario === 'skill-get-previous-on') {
                test_read_mode(true);
            }
            // A previous owner of skill-get whose callback was never audited for read mode.
            wp_register_ability('vormbyte/skill-get', [
                'category' => 'skill',
                'execute_callback' => static function (mixed $input): array {
                    update_option('previous_skill_get_side_effect', 'written');
                    return ['found' => true, 'slug' => (string) ($input['slug'] ?? ''), 'content' => 'from previous'];
                },
            ]);
            require $root . '/includes/skills/abilities/skill-get.php';
            \Vormbyte\Skills\Abilities\SkillGet\register();

            return [
                'result' => wp_get_ability('vormbyte/skill-get')?->execute(['slug' => 'unknown-skill']),
                'side_effect' => $GLOBALS['options']['previous_skill_get_side_effect'] ?? null,
            ];
        })(),
        default => ['unknown_scenario' => $scenario],
    };

    echo json_encode($result, JSON_THROW_ON_ERROR);
}
