<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FeatureRegistryTest extends TestCase
{
    public function testRegistrySnapshotOwnershipAndCascadingTransitions(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $features = \Vormbyte\Features\features();
            $GLOBALS['options']['vormbyte_feature_preferences'] = [
                'vormbyte-pro/elementor' => true,
                'vormbyte-pro/elementor-addon' => true,
            ];
            $before = [
                'parent' => $features->is_active('vormbyte-pro/elementor'),
                'child' => $features->is_active('vormbyte-pro/elementor-addon'),
            ];
            $preview = $features->preview_deactivation('vormbyte-pro/elementor');
            $disabled = $features->deactivate('vormbyte-pro/elementor');
            $afterDisable = [
                'parent' => $features->is_active('vormbyte-pro/elementor'),
                'child' => $features->is_active('vormbyte-pro/elementor-addon'),
                'states' => $GLOBALS['options']['vormbyte_feature_preferences'],
                'category_ability' => $features->is_ability_active('vormbyte/elementor-runtime', 'elementor'),
            ];
            $features->activate('vormbyte-pro/elementor');
            $afterParentEnable = [
                'parent' => $features->is_active('vormbyte-pro/elementor'),
                'child' => $features->is_active('vormbyte-pro/elementor-addon'),
            ];
            $features->activate('vormbyte-pro/elementor-addon');
            $infrastructureTransition = $features->deactivate('vormbyte/infrastructure');
            echo json_encode([
                'before' => $before,
                'preview' => [
                    'features' => $preview->features,
                    'skills' => $preview->skills,
                    'abilities' => $preview->abilities,
                ],
                'disabled_applied' => $disabled->applied,
                'after_disable' => $afterDisable,
                'after_parent_enable' => $afterParentEnable,
                'after_child_enable' => [
                    'parent' => $features->is_active('vormbyte-pro/elementor'),
                    'child' => $features->is_active('vormbyte-pro/elementor-addon'),
                ],
                'owners' => [
                    'design_skill' => $features->feature_for_skill('vormbyte-design')->id,
                    'core_skill' => $features->feature_for_skill('skill-creator')->id,
                    'core_ability' => $features->feature_for_ability('vormbyte/skill-get')->id,
                    'adapter_ability' => $features->feature_for_ability('vormbyte-mcp-adapter/execute-ability')->id,
                    'design_prompt' => $features->feature_for_ability('vormbyte/skill-prompt-vormbyte-design')->id,
                    'category_ability' => $features->feature_for_ability('vormbyte/elementor-runtime', 'elementor')->id,
                    'external_category_ability' => $features->feature_for_ability(
                        'woocommerce/order-add-note',
                        'woocommerce',
                    )?->id,
                    'standalone' => $features->feature_for_ability('third-party/custom')?->id,
                ],
                'infrastructure' => [
                    'visible' => $features->definition('vormbyte/infrastructure')->visible,
                    'toggleable' => $features->definition('vormbyte/infrastructure')->toggleable,
                    'active' => $features->is_active('vormbyte/infrastructure'),
                    'disable_applied' => $infrastructureTransition->applied,
                ],
                'experimental' => [
                    'visual' => $features->definition('vormbyte/visual')->experimental,
                    'elementor' => $features->definition('vormbyte-pro/elementor')->experimental,
                ],
                'queue_owner' => $features->feature_for_ability('vormbyte/gutenberg-add-pending-change')->id,
                'ability_management' => [
                    'feature_owned' => vormbyte_ability_can_be_managed_individually('vormbyte-pro/elementor-build'),
                    'category_owned' => vormbyte_ability_can_be_managed_individually('vormbyte/elementor-runtime'),
                    'standalone' => vormbyte_ability_can_be_managed_individually('third-party/custom'),
                ],
                'confirmations' => [
                    'singular' => \Vormbyte\Features\Admin\feature_confirmation('disable', 'Chat', 0),
                    'cascade' => \Vormbyte\Features\Admin\feature_confirmation('enable', 'Elementor Addon', 1),
                ],
                'errors' => $features->errors(),
                'filter_calls' => $GLOBALS['feature_filter_calls'],
            ]);
            PHP);

        self::assertSame(['parent' => true, 'child' => true], $result['before']);
        self::assertContains('vormbyte-pro/elementor', $result['preview']['features']);
        self::assertContains('vormbyte-pro/elementor-addon', $result['preview']['features']);
        self::assertContains('elementor-build', $result['preview']['skills']);
        self::assertContains('addon-build', $result['preview']['skills']);
        self::assertTrue($result['disabled_applied']);
        self::assertFalse($result['after_disable']['states']['vormbyte-pro/elementor']);
        self::assertFalse($result['after_disable']['states']['vormbyte-pro/elementor-addon']);
        self::assertFalse($result['after_disable']['parent']);
        self::assertFalse($result['after_disable']['child']);
        self::assertFalse($result['after_disable']['category_ability']);
        self::assertSame(['parent' => true, 'child' => false], $result['after_parent_enable']);
        self::assertSame(['parent' => true, 'child' => true], $result['after_child_enable']);
        self::assertSame([
            'design_skill' => 'vormbyte/design',
            'core_skill' => 'vormbyte/infrastructure',
            'core_ability' => 'vormbyte/infrastructure',
            'adapter_ability' => 'vormbyte/infrastructure',
            'design_prompt' => 'vormbyte/design',
            'category_ability' => 'vormbyte-pro/elementor',
            'external_category_ability' => null,
            'standalone' => null,
        ], $result['owners']);
        self::assertSame([
            'visible' => false,
            'toggleable' => false,
            'active' => true,
            'disable_applied' => false,
        ], $result['infrastructure']);
        self::assertSame(['visual' => true, 'elementor' => false], $result['experimental']);
        self::assertSame('vormbyte/block-editor-queue', $result['queue_owner']);
        self::assertSame([
            'feature_owned' => false,
            'category_owned' => false,
            'standalone' => true,
        ], $result['ability_management']);
        self::assertSame(
            'Disable Chat? Settings and stored data will be preserved.',
            $result['confirmations']['singular'],
        );
        self::assertSame(
            'Enable Elementor Addon? This will also activate 1 required feature.',
            $result['confirmations']['cascade'],
        );
        self::assertSame([], $result['errors']);
        self::assertSame(1, $result['filter_calls']);
    }

    public function testLifecycleUsesManifestCallbacks(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $features = \Vormbyte\Features\features();
            $features->boot_active();
            $features->deactivate('vormbyte-pro/elementor');
            echo json_encode([
                'booted' => $GLOBALS['booted'],
                'deactivated' => $GLOBALS['deactivated'],
            ]);
            PHP);

        self::assertSame(['elementor', 'addon'], $result['booted']);
        self::assertSame(['addon', 'elementor'], $result['deactivated']);
    }

    public function testLegacyPreferenceMigratesOnceAndFeatureStoreControlsContext(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $GLOBALS['options']['vormbyte_instructions_enabled'] = '0';
            $features = \Vormbyte\Features\features();
            $beforeBoot = $features->is_active('vormbyte/user-context');
            $writesBeforeBoot = $GLOBALS['update_calls'];
            $features->boot_active();
            $migrated = $GLOBALS['options']['vormbyte_feature_preferences']['vormbyte/user-context'];
            $features->activate('vormbyte/user-context');
            $GLOBALS['options']['vormbyte_instructions_enabled'] = '0';
            echo json_encode([
                'before_boot' => $beforeBoot,
                'writes_before_boot' => $writesBeforeBoot,
                'migrated' => $migrated,
                'active' => $features->is_active('vormbyte/user-context'),
                'runtime_enabled' => \Vormbyte\Context\instructions_is_enabled(),
                'legacy_unchanged' => $GLOBALS['options']['vormbyte_instructions_enabled'],
                'migration_supported' => (new \Vormbyte\Features\Registrar())->supports_legacy_preference_migration(),
            ]);
            PHP);

        self::assertFalse($result['before_boot']);
        self::assertSame(0, $result['writes_before_boot']);
        self::assertFalse($result['migrated']);
        self::assertTrue($result['active']);
        self::assertTrue($result['runtime_enabled']);
        self::assertSame('0', $result['legacy_unchanged']);
        self::assertTrue($result['migration_supported']);
    }

    public function testCentralPreferenceWinsOverLegacyPreference(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $GLOBALS['options']['vormbyte_feature_preferences'] = ['vormbyte/user-context' => true];
            $GLOBALS['options']['vormbyte_instructions_enabled'] = '0';
            $features = \Vormbyte\Features\features();
            echo json_encode([
                'active' => $features->is_active('vormbyte/user-context'),
                'runtime_enabled' => \Vormbyte\Context\instructions_is_enabled(),
                'states' => $GLOBALS['options']['vormbyte_feature_preferences'],
            ]);
            PHP);

        self::assertTrue($result['active']);
        self::assertTrue($result['runtime_enabled']);
        self::assertTrue($result['states']['vormbyte/user-context']);
    }

    public function testUserContextStateSwitchesBetweenDisabledNoticeAndEditor(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $features = \Vormbyte\Features\features();
            $features->deactivate('vormbyte/user-context');
            ob_start();
            \Vormbyte\Context\render_user_context_state();
            $disabledSection = ob_get_clean();

            $features->activate('vormbyte/user-context');
            ob_start();
            \Vormbyte\Context\render_user_context_state();
            $enabledSection = ob_get_clean();

            echo json_encode([
                'disabled_section' => $disabledSection,
                'enabled_section' => $enabledSection,
            ]);
            PHP);

        self::assertStringContainsString('vormbyte-context-disabled', $result['disabled_section']);
        self::assertStringContainsString('vormbyte-features#vormbyte-user-context', $result['disabled_section']);
        self::assertStringNotContainsString('instructions_content', $result['disabled_section']);
        self::assertStringContainsString('vormbyte-user-context-heading', $result['enabled_section']);
        self::assertStringNotContainsString('vormbyte-context-disabled', $result['enabled_section']);
    }

    public function testAbilityHubReactivatesLegacyRuleAndExplainsSpecializationOwnership(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $legacyRules = [
                'vormbyte-pro/elementor-build' => ['disabled' => true],
                'vormbyte/elementor-runtime' => ['disabled' => true],
                'third-party/custom' => ['disabled' => true],
            ];
            $row = vormbyte_build_registered_ability_row(
                new WP_Ability('vormbyte-pro/elementor-build'),
                $legacyRules,
            );
            vormbyte_apply_ability_policy_rule(
                new WP_Ability('vormbyte/elementor-runtime', 'elementor'),
                $legacyRules,
            );
            vormbyte_apply_ability_policy_rule(new WP_Ability('third-party/custom'), $legacyRules);
            $infrastructureRow = vormbyte_build_registered_ability_row(
                new WP_Ability('vormbyte/agent-context'),
                $legacyRules,
            );
            $standaloneRow = vormbyte_build_registered_ability_row(
                new WP_Ability('third-party/custom'),
                $legacyRules,
            );
            $enabledStandaloneRow = vormbyte_build_registered_ability_row(
                new WP_Ability('third-party/custom'),
                [],
            );
            $officialWooCommerceRow = vormbyte_build_registered_ability_row(
                new WP_Ability('woocommerce/order-add-note', 'woocommerce'),
                $legacyRules,
            );
            ob_start();
            vormbyte_render_ability_hub_row($row);
            $html = (string) ob_get_clean();
            ob_start();
            vormbyte_render_ability_rows([$infrastructureRow, $standaloneRow]);
            $mixedHtml = (string) ob_get_clean();
            ob_start();
            vormbyte_render_ability_group_action('Code Execution', [$infrastructureRow, $standaloneRow], 'category');
            $categoryActionHtml = (string) ob_get_clean();
            ob_start();
            vormbyte_render_ability_group_action(
                'Admin Access',
                [$infrastructureRow, $enabledStandaloneRow],
                'category',
            );
            $disableCategoryActionHtml = (string) ob_get_clean();
            ob_start();
            vormbyte_render_ability_managed_owner_meta([$row]);
            vormbyte_render_ability_group_action('Advanced Custom Fields', [$row], 'category');
            $managedCategoryHtml = (string) ob_get_clean();
            ob_start();
            vormbyte_render_ability_group_action('third-party', [$standaloneRow], 'provider');
            $providerActionHtml = (string) ob_get_clean();
            echo json_encode([
                'status' => $row['status'],
                'individually_manageable' => $row['individually_manageable'],
                'manager_kind' => $row['manager_kind'],
                'official_woocommerce' => [
                    'individually_manageable' => $officialWooCommerceRow['individually_manageable'],
                    'managed_by_feature' => $officialWooCommerceRow['managed_by_feature'],
                    'manager_label' => $officialWooCommerceRow['manager_label'],
                ],
                'unregistered' => $GLOBALS['unregistered'],
                'html' => $html,
                'mixed_html' => $mixedHtml,
                'category_action_html' => $categoryActionHtml,
                'disable_category_action_html' => $disableCategoryActionHtml,
                'managed_category_html' => $managedCategoryHtml,
                'provider_action_html' => $providerActionHtml,
                'group_action_labels' => [
                    vormbyte_ability_group_action_label('disable', 'category'),
                    vormbyte_ability_group_action_label('enable', 'configurable'),
                    vormbyte_ability_group_action_label('disable', 'provider'),
                ],
            ]);
            PHP);

        self::assertSame('Enabled', $result['status']);
        self::assertFalse($result['individually_manageable']);
        self::assertSame('specialization', $result['manager_kind']);
        self::assertSame([
            'individually_manageable' => true,
            'managed_by_feature' => false,
            'manager_label' => '',
        ], $result['official_woocommerce']);
        self::assertSame(['third-party/custom'], $result['unregistered']);
        self::assertStringNotContainsString('Managed together', $result['html']);
        self::assertStringNotContainsString('title="This ability', $result['html']);
        self::assertStringContainsString('cannot be selected individually', $result['managed_category_html']);
        self::assertStringNotContainsString('Managed by Elementor', $result['html']);
        self::assertStringNotContainsString('Manage in Features', $result['html']);
        self::assertStringNotContainsString('type="checkbox"', $result['html']);
        self::assertStringContainsString('agent-context', $result['mixed_html']);
        self::assertStringContainsString('Required by Vormbyte', $result['mixed_html']);
        self::assertStringNotContainsString('type="checkbox"', $result['mixed_html']);
        self::assertStringContainsString('Enable configurable abilities', $result['category_action_html']);
        self::assertStringContainsString('Disable 1 ability in Admin Access?', $result['disable_category_action_html']);
        self::assertStringNotContainsString('configurable ability in', $result['disable_category_action_html']);
        self::assertStringContainsString('name="ability_names[]"', $result['category_action_html']);
        self::assertStringNotContainsString('vormbyte/agent-context', $result['category_action_html']);
        self::assertStringContainsString('Managed by Vormbyte Pro', $result['managed_category_html']);
        self::assertStringContainsString('Manage in Features', $result['managed_category_html']);
        self::assertStringContainsString('data-tooltip=', $result['managed_category_html']);
        self::assertStringNotContainsString('Disable category', $result['managed_category_html']);
        self::assertStringContainsString('Enable all abilities', $result['provider_action_html']);
        self::assertSame(
            ['Disable category', 'Enable configurable abilities', 'Disable all abilities'],
            $result['group_action_labels'],
        );
    }

    public function testInvalidDefinitionCannotBeActivated(): void
    {
        $result = $this->runRegistryScript(
            <<<'PHP'
                $features = \Vormbyte\Features\features();
                $transition = $features->activate('vormbyte-pro/invalid');
                echo json_encode([
                    'applied' => $transition->applied,
                    'active' => $features->is_active('vormbyte-pro/invalid'),
                    'blockers' => $transition->blockers,
                    'errors' => $features->errors(),
                ]);
                PHP,
            includeInvalid: true,
        );

        self::assertFalse($result['applied']);
        self::assertFalse($result['active']);
        self::assertNotSame([], $result['blockers']);
        self::assertNotSame([], $result['errors']);
    }

    public function testStateReadsDoNotPersistUntilBootReconciliation(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $GLOBALS['options']['vormbyte_feature_preferences'] = ['removed/feature' => true];
            $features = \Vormbyte\Features\features();
            $activeBeforeBoot = $features->is_active('vormbyte-pro/elementor');
            $writesBeforeBoot = $GLOBALS['update_calls'];
            $features->boot_active();
            echo json_encode([
                'active_before_boot' => $activeBeforeBoot,
                'writes_before_boot' => $writesBeforeBoot,
                'writes_after_boot' => $GLOBALS['update_calls'],
                'states' => $GLOBALS['options']['vormbyte_feature_preferences'],
            ]);
            PHP);

        self::assertTrue($result['active_before_boot']);
        self::assertSame(0, $result['writes_before_boot']);
        self::assertSame(1, $result['writes_after_boot']);
        self::assertArrayNotHasKey('removed/feature', $result['states']);
    }

    public function testSharedSkillRemainsActiveUntilItsLastFeatureIsDisabled(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $features = \Vormbyte\Features\features();
            $firstPreview = $features->preview_deactivation('vormbyte-pro/shared-one');
            $features->deactivate('vormbyte-pro/shared-one');
            $activeAfterFirst = $features->is_skill_active('shared-workflow');
            $secondPreview = $features->preview_deactivation('vormbyte-pro/shared-two');
            $features->deactivate('vormbyte-pro/shared-two');
            echo json_encode([
                'managers' => array_map(
                    static fn($feature) => $feature->id,
                    $features->features_for_skill('shared-workflow'),
                ),
                'first_impact' => $firstPreview->skills,
                'active_after_first' => $activeAfterFirst,
                'second_impact' => $secondPreview->skills,
                'active_after_second' => $features->is_skill_active('shared-workflow'),
            ]);
            PHP);

        self::assertSame(['vormbyte-pro/shared-one', 'vormbyte-pro/shared-two'], $result['managers']);
        self::assertNotContains('shared-workflow', $result['first_impact']);
        self::assertTrue($result['active_after_first']);
        self::assertContains('shared-workflow', $result['second_impact']);
        self::assertFalse($result['active_after_second']);
    }

    public function testFeatureLabelsAreTranslatedAfterInitAndLegacyPropertiesRemainReadable(): void
    {
        $result = $this->runRegistryScript(<<<'PHP'
            $features = \Vormbyte\Features\features();
            $visual = $features->definition('vormbyte/visual');
            $labelBeforeInit = $visual->label();
            $legacyLabelBeforeInit = $visual->label;
            $translationsBeforeInit = $GLOBALS['translation_calls'];
            $GLOBALS['init_calls'] = 1;
            $label = $visual->label;
            $description = $visual->description;
            $visual->label();
            echo json_encode([
                'translations_during_boot' => $GLOBALS['translations_during_boot'],
                'translations_before_init' => $translationsBeforeInit,
                'label_before_init' => $labelBeforeInit,
                'legacy_label_before_init' => $legacyLabelBeforeInit,
                'label' => $label,
                'description' => $description,
                'translations_after_init' => $GLOBALS['translation_calls'],
                'plain_string_label' => $features->definition('vormbyte-pro/elementor')->label,
            ]);
            PHP);

        self::assertSame(0, $result['translations_during_boot']);
        self::assertSame(0, $result['translations_before_init']);
        self::assertSame('Vormbyte Visual', $result['label_before_init']);
        self::assertSame('Vormbyte Visual', $result['legacy_label_before_init']);
        self::assertSame('Vormbyte Visual', $result['label']);
        self::assertSame(
            'The live browser workspace and its editor integrations for watching and guiding an AI agent.',
            $result['description'],
        );
        self::assertSame(2, $result['translations_after_init']);
        self::assertSame('Elementor', $result['plain_string_label']);
    }

    /** @return array<string, mixed> */
    private function runRegistryScript(string $body, bool $includeInvalid = false): array
    {
        $root = dirname(__DIR__, levels: 2);
        $bootstrap = <<<'PHP'
            define('ABSPATH', '/');
            $GLOBALS['options'] = [];
            $GLOBALS['feature_filter_calls'] = 0;
            $GLOBALS['booted'] = [];
            $GLOBALS['deactivated'] = [];
            $GLOBALS['unregistered'] = [];
            $GLOBALS['update_calls'] = 0;
            $GLOBALS['translation_calls'] = 0;
            $GLOBALS['translations_before_init'] = 0;
            $GLOBALS['init_calls'] = 0;
            $GLOBALS['include_invalid_feature'] = __INCLUDE_INVALID__;
            $GLOBALS['blog_id'] = 1;

            class WP_Ability {
                public function __construct(private string $name, private string $category = '') {}
                public function get_name(): string { return $this->name; }
                public function get_label(): string { return $this->name; }
                public function get_description(): string { return ''; }
                public function get_category(): string { return $this->category; }
                public function get_meta(): array { return ['mcp' => ['public' => true]]; }
            }

            function __(string $text, string $domain = 'default'): string {
                $GLOBALS['translation_calls']++;
                return $text;
            }
            function did_action(string $hook): int { return $hook === 'init' ? $GLOBALS['init_calls'] : 0; }
            function vormbyte_is_read_mode(): bool { return false; }
            function _n(string $single, string $plural, int $number, string $domain = 'default'): string {
                return $number === 1 ? $single : $plural;
            }
            function admin_url(string $path = ''): string { return 'https://example.test/wp-admin/' . $path; }
            function add_query_arg(array $args, string $url): string { return $url . '?' . http_build_query($args); }
            function wp_unslash(mixed $value): mixed { return $value; }
            function sanitize_html_class(string $class): string { return str_replace('/', '-', $class); }
            function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES); }
            function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES); }
            function esc_js(string $text): string { return addslashes($text); }
            function esc_html__(string $text, string $domain = 'default'): string { return esc_html($text); }
            function esc_attr__(string $text, string $domain = 'default'): string { return esc_attr($text); }
            function esc_url(string $url): string { return $url; }
            function esc_textarea(string $text): string { return htmlspecialchars($text, ENT_QUOTES); }
            function esc_html_e(string $text, string $domain = 'default'): void { echo esc_html($text); }
            function wp_nonce_field(string $action = '-1'): void {
                echo '<input type="hidden" name="_wpnonce" value="test-nonce" />';
            }
            function do_action(string $hook, mixed ...$args): void {
                if ($hook !== 'vormbyte_register_features') { return; }
                $GLOBALS['feature_filter_calls']++;
                $args[0]->register_many(test_feature_manifest());
            }
            function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
            function test_feature_manifest(): array {
                $value = [];
                $value['vormbyte-pro/elementor'] = [
                    'kind' => 'specialization',
                    'provider' => 'Vormbyte Pro',
                    'label' => 'Elementor',
                    'default_active' => true,
                    'depends_on' => [],
                    'skills' => ['elementor-build'],
                    'abilities' => ['vormbyte-pro/elementor-build'],
                    'ability_categories' => ['elementor'],
                    'boot' => 'test_elementor_boot',
                    'deactivate' => 'test_elementor_deactivate',
                ];
                $value['vormbyte-pro/elementor-addon'] = [
                    'kind' => 'specialization',
                    'provider' => 'Vormbyte Pro',
                    'label' => 'Elementor Addon',
                    'default_active' => true,
                    'depends_on' => ['vormbyte-pro/elementor'],
                    'skills' => ['addon-build'],
                    'abilities' => ['vormbyte-pro/addon-build'],
                    'boot' => 'test_addon_boot',
                    'deactivate' => 'test_addon_deactivate',
                ];
                $value['vormbyte-pro/shared-one'] = [
                    'kind' => 'specialization',
                    'provider' => 'Vormbyte Pro',
                    'label' => 'Shared One',
                    'default_active' => true,
                    'depends_on' => [],
                    'skills' => ['shared-workflow' => 'shared'],
                    'abilities' => [],
                ];
                $value['vormbyte-pro/shared-two'] = [
                    'kind' => 'specialization',
                    'provider' => 'Vormbyte Pro',
                    'label' => 'Shared Two',
                    'default_active' => true,
                    'depends_on' => [],
                    'skills' => ['shared-workflow' => 'shared'],
                    'abilities' => [],
                ];
                $value['vormbyte-pro/woocommerce'] = [
                    'kind' => 'specialization',
                    'provider' => 'Vormbyte Pro',
                    'label' => 'WooCommerce',
                    'default_active' => true,
                    'depends_on' => [],
                    'skills' => [],
                    'abilities' => [],
                    'ability_categories' => ['woocommerce'],
                ];
                if ($GLOBALS['include_invalid_feature']) {
                    $value['vormbyte-pro/invalid'] = [
                        'kind' => 'specialization',
                        'provider' => 'Vormbyte Pro',
                        'label' => 'Invalid',
                        'default_active' => true,
                        'depends_on' => ['vormbyte-pro/unknown'],
                        'skills' => [],
                        'abilities' => [],
                    ];
                }
                return $value;
            }
            function test_elementor_boot(): void { $GLOBALS['booted'][] = 'elementor'; }
            function test_addon_boot(): void { $GLOBALS['booted'][] = 'addon'; }
            function test_elementor_deactivate(): void { $GLOBALS['deactivated'][] = 'elementor'; }
            function test_addon_deactivate(): void { $GLOBALS['deactivated'][] = 'addon'; }
            function vormbyte_boot_design_feature(): void {}
            function vormbyte_boot_chat_feature(): void {}
            function vormbyte_boot_visual_feature(): void {}
            function vormbyte_boot_block_editor_queue_feature(): void {}
            function vormbyte_deactivate_block_editor_queue_feature(): void {}
            function get_option(string $name, mixed $default_value = false): mixed {
                return $GLOBALS['options'][$name] ?? $default_value;
            }
            function update_option(string $name, mixed $value, bool $autoload = true): bool {
                $GLOBALS['update_calls']++;
                $GLOBALS['options'][$name] = $value;
                return true;
            }
            function get_site_option(string $name, mixed $default_value = false): mixed { return $default_value; }
            function get_current_blog_id(): int { return $GLOBALS['blog_id']; }
            function is_multisite(): bool { return false; }
            function wp_get_ability(string $name): ?WP_Ability {
                return match ($name) {
                    'vormbyte-pro/elementor-build' => new WP_Ability($name, 'elementor'),
                    'vormbyte/elementor-runtime' => new WP_Ability($name, 'elementor'),
                    'third-party/custom' => new WP_Ability($name),
                    default => null,
                };
            }
            function wp_get_ability_category(string $slug): ?object { return null; }
            function wp_unregister_ability(string $name): bool {
                $GLOBALS['unregistered'][] = $name;
                return true;
            }
            require $argv[1] . '/includes/features/api.php';
            require $argv[1] . '/includes/instructions-admin.php';
            \Vormbyte\Features\initialize_features();
            $GLOBALS['translations_during_boot'] = $GLOBALS['translation_calls'];
            $GLOBALS['translations_before_init'] = $GLOBALS['translation_calls'];
            require $argv[1] . '/includes/features/admin.php';
            require $argv[1] . '/includes/helpers.php';
            require $argv[1] . '/includes/admin-page.php';
            PHP;
        $bootstrap = str_replace('__INCLUDE_INVALID__', $includeInvalid ? 'true' : 'false', $bootstrap);
        $script = $bootstrap . "\n" . $body;
        $command = sprintf(
            '%s -r %s %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($script),
            escapeshellarg($root),
        );
        $output = (string) shell_exec($command);
        $decoded = json_decode($output, associative: true);
        self::assertIsArray($decoded, $output);

        return $decoded;
    }
}
