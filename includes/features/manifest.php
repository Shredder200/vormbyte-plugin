<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Features;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * @param \Closure(): string $translate
 * @return array{source: string, translate: \Closure(): string}
 */
function deferred_text(string $source, \Closure $translate): array
{
    return ['source' => $source, 'translate' => $translate];
}

/** @return array<string, array<string, mixed>> */
function core_manifest(): array
{
    return [
        'vormbyte/infrastructure' => [
            'kind' => 'feature',
            'provider' => 'Vormbyte',
            'label' => deferred_text('Vormbyte Infrastructure', static fn(): string => __(
                'Vormbyte Infrastructure',
                domain: 'vormbyte',
            )),
            'description' => deferred_text('Infrastructure required for Vormbyte to expose and load skills safely.', static fn(): string => __(
                'Infrastructure required for Vormbyte to expose and load skills safely.',
                domain: 'vormbyte',
            )),
            'toggleable' => false,
            'visible' => false,
            'default_active' => true,
            'depends_on' => [],
            'skills' => ['skill-creator', 'wordpress-site-editing', 'page-builder-editing', 'divi-editing', 'wordpress-troubleshooting'],
            'abilities' => [
                'vormbyte/skill-get',
                'vormbyte/agent-context',
                'vormbyte/skill-prompt-skill-creator',
                'vormbyte/skill-prompt-wordpress-site-editing',
                'vormbyte/skill-prompt-page-builder-editing',
                'vormbyte/skill-prompt-divi-editing',
                'vormbyte/skill-prompt-wordpress-troubleshooting',
                'vormbyte-mcp-adapter/discover-abilities',
                'vormbyte-mcp-adapter/get-ability-info',
                'vormbyte-mcp-adapter/execute-ability',
            ],
        ],
        'vormbyte/user-context' => [
            'kind' => 'feature',
            'provider' => 'Vormbyte',
            'label' => deferred_text('User Context', static fn(): string => __('User Context', domain: 'vormbyte')),
            'description' => deferred_text('Site-specific instructions added to the system context received by connected agents.', static fn(): string => __(
                'Site-specific instructions added to the system context received by connected agents.',
                domain: 'vormbyte',
            )),
            'default_active' => true,
            'depends_on' => [],
            'skills' => [],
            'abilities' => [],
            'legacy_options' => ['vormbyte_instructions_enabled', 'nvp_instructions_enabled'],
        ],
        'vormbyte/design' => [
            'kind' => 'feature',
            'provider' => 'Vormbyte',
            'label' => deferred_text('Vormbyte Design', static fn(): string => __(
                'Vormbyte Design',
                domain: 'vormbyte',
            )),
            'description' => deferred_text(
                'One saved design system your AI follows on every page it builds, so the site stays consistent and looks deliberate rather than generic.',
                static fn(): string => __(
                    'One saved design system your AI follows on every page it builds, so the site stays consistent and looks deliberate rather than generic.',
                    domain: 'vormbyte',
                ),
            ),
            'default_active' => true,
            'depends_on' => [],
            'skills' => ['vormbyte-design', 'custom-theme-build'],
            'abilities' => [
                'vormbyte/list-design-library',
                'vormbyte/get-active-design',
                'vormbyte/activate-design',
                'vormbyte/save-design',
                'vormbyte/check-design',
                'vormbyte/get-design',
                'vormbyte/delete-design',
                'vormbyte/skill-prompt-vormbyte-design',
                'vormbyte/skill-prompt-custom-theme-build',
            ],
            'boot' => 'vormbyte_boot_design_feature',
        ],
        'vormbyte/chat' => [
            'kind' => 'feature',
            'provider' => 'Vormbyte',
            'label' => deferred_text('Vormbyte Chat', static fn(): string => __('Vormbyte Chat', domain: 'vormbyte')),
            'description' => deferred_text(
                'An AI agent inside your WordPress dashboard: describe a change in plain language, review its plan, and approve it to apply the change.',
                static fn(): string => __(
                    'An AI agent inside your WordPress dashboard: describe a change in plain language, review its plan, and approve it to apply the change.',
                    domain: 'vormbyte',
                ),
            ),
            'default_active' => true,
            'depends_on' => [],
            'skills' => [],
            'abilities' => [],
            'boot' => 'vormbyte_boot_chat_feature',
        ],
        'vormbyte/visual' => [
            'kind' => 'feature',
            'provider' => 'Vormbyte',
            'label' => deferred_text('Vormbyte Visual', static fn(): string => __(
                'Vormbyte Visual',
                domain: 'vormbyte',
            )),
            'description' => deferred_text('The live browser workspace and its editor integrations for watching and guiding an AI agent.', static fn(): string => __(
                'The live browser workspace and its editor integrations for watching and guiding an AI agent.',
                domain: 'vormbyte',
            )),
            'experimental' => true,
            'default_active' => true,
            'depends_on' => [],
            'skills' => [],
            'abilities' => [],
            'boot' => 'vormbyte_boot_visual_feature',
        ],
        'vormbyte/block-editor-queue' => [
            'kind' => 'feature',
            'provider' => 'Vormbyte',
            'label' => deferred_text('Block Editor Queue', static fn(): string => __(
                'Block Editor Queue',
                domain: 'vormbyte',
            )),
            'description' => deferred_text('The browser-backed Gutenberg authoring workflow, including queue storage, finalization, REST routes, and abilities.', static fn(): string => __(
                'The browser-backed Gutenberg authoring workflow, including queue storage, finalization, REST routes, and abilities.',
                domain: 'vormbyte',
            )),
            'default_active' => true,
            'depends_on' => [],
            'skills' => ['gutenberg-edit-content'],
            'abilities' => [
                'vormbyte/gutenberg-get-finalizer-runtime',
                'vormbyte/gutenberg-list-block-types',
                'vormbyte/gutenberg-get-block-type',
                'vormbyte/gutenberg-get-content',
                'vormbyte/gutenberg-write-content',
                'vormbyte/gutenberg-create-pending-batch',
                'vormbyte/gutenberg-add-pending-change',
                'vormbyte/gutenberg-enable-batch-finalization',
                'vormbyte/gutenberg-get-pending-batch',
                'vormbyte/gutenberg-list-pending-batches',
                'vormbyte/gutenberg-delete-pending-batch',
                'vormbyte/gutenberg-delete-pending-change',
                'vormbyte/gutenberg-get-finalization-url',
                'vormbyte/skill-prompt-gutenberg-edit-content',
            ],
            'boot' => 'vormbyte_boot_block_editor_queue_feature',
            'deactivate' => 'vormbyte_deactivate_block_editor_queue_feature',
        ],
    ];
}
