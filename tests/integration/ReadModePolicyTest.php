<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Read mode keeps only an allowlist of Abilities registered, on every request, and tells the agent why
 * everything else is gone. The allowlist is Vormbyte's own read abilities plus whatever the single
 * vormbyte_read_mode_abilities filter adds (Vormbyte Pro's, stood in for here). Outside read mode the
 * Ability policy behaves exactly as before.
 */
final class ReadModePolicyTest extends TestCase
{
    private const BASE_ALLOWLIST = [
        'vormbyte/agent-context',
        'vormbyte-mcp-adapter/discover-abilities',
        'vormbyte-mcp-adapter/get-ability-info',
        'vormbyte-mcp-adapter/execute-ability',
        'vormbyte/read-file',
        'vormbyte/list-directory',
    ];

    /** The base allowlist plus the data abilities that the stand-in for Vormbyte Pro adds. */
    private const EXPECTED_ALLOWLIST = [
        'vormbyte/agent-context',
        'vormbyte-mcp-adapter/discover-abilities',
        'vormbyte-mcp-adapter/get-ability-info',
        'vormbyte-mcp-adapter/execute-ability',
        'vormbyte/read-file',
        'vormbyte/list-directory',
        'vormbyte/get-object',
        'vormbyte/query-objects',
        'vormbyte/get-options',
        'vormbyte/list-options',
        'vormbyte/describe-schema',
        'vormbyte/read-query',
    ];

    private const WRITE_ABILITIES = [
        'vormbyte/execute-php',
        'vormbyte/run-wp-cli',
        'vormbyte/get-wp-cli-job',
        'vormbyte/write-file',
        'vormbyte/edit-file',
        'vormbyte/delete-file',
        'vormbyte/enable-file',
        'vormbyte/disable-file',
        'vormbyte/create-upload-link',
        'vormbyte/create-admin-access-link',
        'vormbyte/gutenberg-write-content',
        'vormbyte/gutenberg-add-pending-change',
        'vormbyte/gutenberg-create-pending-batch',
        'vormbyte/gutenberg-enable-batch-finalization',
        'vormbyte/gutenberg-delete-pending-batch',
        'vormbyte/gutenberg-delete-pending-change',
        'vormbyte/save-design',
        'vormbyte/activate-design',
        'vormbyte/delete-design',
        'vormbyte/skill-write',
        'vormbyte/skill-edit',
        'vormbyte/skill-delete',
    ];

    public function testDefaultAllowlistIsExplicit(): void
    {
        $result = self::runScenario('policy-on');

        self::assertSame(self::EXPECTED_ALLOWLIST, $result['allowed']);
    }

    public function testReadModeUnregistersEverythingOutsideTheAllowlist(): void
    {
        $result = self::runScenario('policy-on');

        self::assertSame(self::sorted(self::EXPECTED_ALLOWLIST), self::sorted($result['registered']));
        foreach (self::WRITE_ABILITIES as $ability) {
            self::assertContains($ability, $result['unregistered'], $ability . ' survived read mode.');
        }
        // Side-effecting "get" abilities, prompts, core abilities, and a provider's readonly annotation do not count.
        foreach ([
            'vormbyte/gutenberg-get-content',
            'vormbyte/gutenberg-get-pending-batch',
            'vormbyte/gutenberg-list-pending-batches',
            'vormbyte/gutenberg-get-finalization-url',
            'vormbyte/gutenberg-get-finalizer-runtime',
            'vormbyte/skill-prompt-skill-creator',
            // Skills and Vormbyte Design are not offered in read mode.
            'vormbyte/skill-get',
            'vormbyte/list-design-library',
            'vormbyte/get-active-design',
            'vormbyte/get-design',
            'vormbyte/check-design',
            // Nor are the Block Editor Queue's block type reads.
            'vormbyte/gutenberg-list-block-types',
            'vormbyte/gutenberg-get-block-type',
            'core/get-site-info',
            'acme/delete-everything',
        ] as $ability) {
            self::assertContains($ability, $result['unregistered'], $ability . ' survived read mode.');
        }
    }

    public function testPolicyOutsideReadModeIsUnchanged(): void
    {
        $result = self::runScenario('policy-off');

        self::assertSame(['acme/report'], $result['unregistered']);
        self::assertContains('vormbyte/execute-php', $result['registered']);
        self::assertContains('vormbyte-mcp-adapter/execute-ability', $result['registered']);
    }

    /**
     * Hub rules still apply on top of read mode to the read abilities it does not require, whoever owns them.
     * The ones it requires stay on whatever a rule says.
     */
    public function testHubRulesApplyToTheReadAbilitiesReadModeDoesNotRequire(): void
    {
        $result = self::runScenario('policy-on-with-rule');

        self::assertNotContains('vormbyte/get-object', $result['registered']);
        self::assertContains('vormbyte/get-object', $result['unregistered']);
        self::assertContains('vormbyte/read-query', $result['registered']);
    }

    public function testManagementScreenStillSkipsHubRulesOutsideReadMode(): void
    {
        $result = self::runScenario('policy-screen-off');

        self::assertSame([], $result['unregistered']);
        self::assertContains('acme/report', $result['registered']);
    }

    public function testManagementScreenDoesNotBypassReadMode(): void
    {
        $result = self::runScenario('policy-screen-on');

        self::assertSame(self::sorted(self::EXPECTED_ALLOWLIST), self::sorted($result['registered']));
        self::assertContains('vormbyte/execute-php', $result['unregistered']);
        // On the screen the hub rules are still not applied, only read mode is.
        self::assertContains('vormbyte/read-query', $result['registered']);
    }

    public function testSkillGetDoesNotForwardToAPreviousProviderInReadMode(): void
    {
        self::assertSame(
            ['result' => ['found' => false], 'side_effect' => null],
            self::runScenario('skill-get-previous-on'),
        );

        // Outside read mode the previous owner still answers slugs Vormbyte's sources do not know.
        self::assertSame(
            [
                'result' => ['found' => true, 'slug' => 'unknown-skill', 'content' => 'from previous'],
                'side_effect' => 'written',
            ],
            self::runScenario('skill-get-previous-off'),
        );
    }

    public function testTheSingleFilterAddsAndRemovesAbilitiesAndDropsUnusableEntries(): void
    {
        $result = self::runScenario('policy-filter');

        // The filter removed read-query and added acme/report with callbacks; the entries without usable
        // callbacks, and the integer name, were dropped.
        $expected = array_values(array_diff(self::EXPECTED_ALLOWLIST, ['vormbyte/read-query']));
        $expected[] = 'acme/report';
        self::assertSame($expected, $result['allowed']);
        self::assertContains('acme/report', $result['registered']);
        self::assertNotContains('vormbyte/execute-php', $result['registered']);
        self::assertNotContains('vormbyte/read-query', $result['registered']);
        self::assertContains('vormbyte/read-file', $result['registered']);

        // Guidance is plain text on one line.
        self::assertContains('- acme/report: A report. Two lines.', $result['instructions']);
    }

    /** A `readonly` annotation alone does not keep an ability, and the abilities other plugins declare are listed as candidates. */
    public function testDeclaredReadAbilitiesAreNotAllowedUnlessTurnedOn(): void
    {
        $result = self::runScenario('declared-off');

        foreach (['acme/declared-read', 'acme/declared-write', 'acme/not-declared', 'vormbyte/look-alike'] as $name) {
            self::assertNotContains($name, $result['registered'], $name);
        }
        self::assertSame(self::EXPECTED_ALLOWLIST, $result['allowed']);
        self::assertFalse($result['allows']['acme/declared-read']);
        self::assertSame([], $result['declared']);
        self::assertFalse($result['guard_before']['acme/declared-read']);

        // The screen that offers them still sees the read abilities other plugins declare, public to MCP.
        self::assertContains('acme/declared-read', $result['candidates']);
        self::assertContains('acme/delete-everything', $result['candidates']);
        foreach (['acme/declared-write', 'acme/not-declared', 'vormbyte/look-alike', 'vormbyte-extra/look-alike', 'acme/report'] as $name) {
            self::assertNotContains($name, $result['candidates'], $name);
        }
    }

    /** Turned on, the abilities other plugins declare read only stay, and nothing else changes. */
    public function testDeclaredReadAbilitiesStayWhenTurnedOn(): void
    {
        $result = self::runScenario('declared-on');

        self::assertContains('acme/declared-read', $result['registered']);
        self::assertContains('acme/delete-everything', $result['registered']);
        foreach ([
            'acme/declared-write',
            'acme/not-declared',
            'acme/report',
            'vormbyte/look-alike',
            'vormbyte-extra/look-alike',
            'vormbyte/execute-php',
            'core/get-site-info',
        ] as $name) {
            self::assertNotContains($name, $result['registered'], $name);
        }

        // The allowlist is the same; declared abilities are allowed by their annotation, not listed in it.
        self::assertSame(self::EXPECTED_ALLOWLIST, $result['allowed']);
        self::assertTrue($result['allows']['acme/declared-read']);
        self::assertFalse($result['allows']['acme/declared-write']);
        self::assertFalse($result['allows']['vormbyte/look-alike']);
        self::assertContains('acme/declared-read', $result['declared']);
        self::assertNotContains('acme/declared-write', $result['declared']);

        // The permission guard agrees before and after the sweep.
        self::assertTrue($result['guard_before']['acme/declared-read']);
        self::assertTrue($result['guard_after']['acme/declared-read']);
        self::assertFalse($result['guard_before']['acme/declared-write']);
        self::assertFalse($result['guard_before']['vormbyte/look-alike']);
        // The sweep removed it, so there is no object left to ask.
        self::assertNull($result['guard_after']['vormbyte/look-alike']);
    }

    public function testWithoutVormbyteProOnlyTheBaseAbilitiesStay(): void
    {
        $result = self::runScenario('no-pro');

        self::assertSame(self::BASE_ALLOWLIST, $result['allowed']);
        foreach (['vormbyte/get-object', 'vormbyte/read-query'] as $name) {
            self::assertNotContains($name, $result['registered']);
            self::assertContains($name, $result['unregistered']);
        }

        // The instructions name only the abilities that are there.
        $instructions = implode("\n", $result['instructions']);
        self::assertStringContainsString('- vormbyte/read-file: ', $instructions);
        self::assertStringContainsString('- vormbyte/agent-context: ', $instructions);
        foreach (['vormbyte/get-object', 'vormbyte/get-options', 'vormbyte/describe-schema', 'vormbyte/read-query'] as $name) {
            self::assertStringNotContainsString($name, $instructions);
        }
        self::assertStringContainsString('Do NOT try to make the change by other means', $instructions);

        // Read mode cannot be used as intended: the agent is told why, in Vormbyte's own words, and to tell the user.
        self::assertStringContainsString('Read mode cannot be used as intended right now', $instructions);
        self::assertStringContainsString('because Vormbyte Pro is not active or its license is not active', $instructions);
        self::assertStringContainsString('Tell the user. A site administrator can turn off read mode to use Vormbyte without it.', $instructions);
        self::assertStringNotContainsString('not because of licensing', $instructions);
        self::assertStringNotContainsString('activating or renewing', $instructions);
    }

    public function testAFilterThatIsNotAListLeavesTheBaseAbilities(): void
    {
        $result = self::runScenario('policy-broken-filter');

        self::assertSame(self::BASE_ALLOWLIST, $result['allowed']);
        self::assertNotContains('vormbyte/execute-php', $result['registered']);
    }

    public function testTheListIsFixedOnceAllPluginsHaveLoaded(): void
    {
        $result = self::runScenario('policy-frozen');

        self::assertSame(self::BASE_ALLOWLIST, $result['before']);
        self::assertSame($result['before'], $result['after']);
    }

    public function testMcpServersKeepTheSameToolsAndOnlyAllowedComponentsInReadMode(): void
    {
        $result = self::runScenario('mcp-tools');
        $trio = [
            'vormbyte-mcp-adapter/discover-abilities',
            'vormbyte-mcp-adapter/get-ability-info',
            'vormbyte-mcp-adapter/execute-ability',
        ];

        self::assertSame(
            ['tools' => $trio, 'resources' => ['discovered/resource'], 'prompts' => ['discovered/prompt']],
            $result['off'],
        );
        self::assertSame(['tools' => $trio, 'server_id' => 'x'], $result['off_config']);

        // The tool list is the same with read mode on, so clients configured once keep working.
        self::assertSame($trio, $result['on']['tools']);
        // Resources and prompts come from the allowlist, never from discovery of public Abilities: the
        // scenario registers skill-get and acme/write-prompt as prompts, and adds only acme/allowed-prompt to
        // the allowlist through the filter.
        self::assertSame([], $result['on']['resources']);
        self::assertSame(['acme/allowed-prompt'], $result['on']['prompts']);
        // Configuration filters cannot add components in read mode.
        self::assertSame(
            ['tools' => $trio, 'resources' => [], 'prompts' => ['acme/allowed-prompt'], 'server_id' => 'x'],
            $result['on_config'],
        );
    }

    public function testRefusalsShareTheReadModeWording(): void
    {
        $result = self::runScenario('errors');

        self::assertNull($result['off']);
        self::assertNull($result['allowed']);
        self::assertNull($result['empty']);
        foreach (['blocked', 'route'] as $key) {
            self::assertSame('vormbyte_read_mode', $result[$key]['error']);
            self::assertSame(['status' => 403], $result[$key]['data']);
            self::assertStringStartsWith('Read mode is active on this site', $result[$key]['message']);
            self::assertStringContainsString('Do NOT try to make the change by other means', $result[$key]['message']);
            self::assertStringContainsString('curl', $result[$key]['message']);
            self::assertStringContainsString('WP-CLI', $result[$key]['message']);
        }
        self::assertStringContainsString("'vormbyte/execute-php'", $result['blocked']['message']);

        $instructions = implode("\n", $result['instructions']);
        self::assertStringContainsString('READ MODE IS ACTIVE', $instructions);
        self::assertStringContainsString('you cannot change it', $instructions);
        self::assertStringContainsString('mcp-adapter-execute-ability', $instructions);
        self::assertStringContainsString('Do NOT try to make the change by other means', $instructions);
    }

    public function testAgentInstructionsAnnounceReadMode(): void
    {
        $result = self::runScenario('instructions');

        self::assertStringStartsWith('Vormbyte gives you full control', $result['off']);
        self::assertStringNotContainsString('READ MODE', $result['off']);
        self::assertStringContainsString('## WordPress-native development', $result['off']);
        self::assertStringContainsString('## Building pages and layout', $result['off']);

        // Read-mode guidance only: what is available, what is not, and no workarounds.
        self::assertStringStartsWith("## READ MODE IS ACTIVE\n", $result['on']);
        foreach (['vormbyte/read-file', 'vormbyte/get-object', 'vormbyte/get-options', 'vormbyte/describe-schema', 'vormbyte/read-query', 'Not available:', 'curl'] as $expected) {
            self::assertStringContainsString($expected, $result['on']);
        }
        foreach (['gives you full control', '## Connection safety', '## Environment', '## WordPress-native development', '## Building pages and layout'] as $absent) {
            self::assertStringNotContainsString($absent, $result['on']);
        }

        // With Vormbyte Pro's abilities, what is missing is unavailable because of read mode, not licensing.
        self::assertStringContainsString('unavailable because read mode is active, not because of licensing', $result['on']);
        self::assertStringNotContainsString('cannot be used as intended', $result['on']);
    }

    /**
     * In read mode every agent-facing surface sends the read-mode guidance alone: the
     * vormbyte_discover_abilities_instructions chain (skill catalog, Pro's additions, the administrator's
     * Context) and Visual's discovery filter are not applied, and no skill source is consulted.
     */
    public function testAgentSurfacesGetReadModeGuidanceOnlyAndNoSkills(): void
    {
        $on = self::runScenario('agent-surfaces-on');

        foreach (['agent_context_instructions', 'discover_instructions', 'chat_instructions', 'visual_instructions', 'admin_preview'] as $surface) {
            self::assertSame($on['read_mode_instructions'], $on[$surface], $surface);
            self::assertStringNotContainsString('INJECTED', $on[$surface], $surface);
        }
        self::assertSame([], $on['agent_context_skills']);
        self::assertSame(['active' => true], $on['agent_context_read_mode']);
        self::assertSame([], $on['skill_prompts']);
        self::assertSame([], $on['skill_source_loads']);
        self::assertStringNotContainsStringIgnoringCase('skill', $on['agent_context_description']);
    }

    public function testAgentSurfacesAreUnchangedOutsideReadMode(): void
    {
        $off = self::runScenario('agent-surfaces-off');

        foreach (['agent_context_instructions', 'discover_instructions', 'chat_instructions', 'admin_preview'] as $surface) {
            self::assertStringStartsWith('Vormbyte gives you full control', $off[$surface], $surface);
            self::assertStringContainsString('INJECTED: skill catalog, Pro memory preview.', $off[$surface], $surface);
        }
        self::assertStringContainsString('INJECTED: Visual extension guidance.', $off['visual_instructions']);
        self::assertSame([['slug' => 'demo-skill', 'description' => 'A demo skill.', 'source' => 'test']], $off['agent_context_skills']);
        self::assertSame(['active' => false], $off['agent_context_read_mode']);
        self::assertSame(['vormbyte/skill-prompt-demo-skill'], $off['skill_prompts']);
        self::assertSame(['agentic', 'prompt'], $off['skill_source_loads']);
        self::assertSame(
            'Return transport-neutral Vormbyte site guidance, environment, and skill summaries.',
            $off['agent_context_description'],
        );
    }

    public function testHooksRegistered(): void
    {
        self::assertSame(
            [
                ['wp_register_ability_args', 'vormbyte_read_mode_guard_ability_args', PHP_INT_MAX],
                ['wp_ability_permission_result', 'vormbyte_read_mode_permission_result', PHP_INT_MAX],
                ['wp_pre_execute_ability', 'vormbyte_read_mode_pre_execute_ability', PHP_INT_MAX],
                ['vormbyte_mcp_adapter_tools_list', 'vormbyte_read_mode_filter_mcp_list', PHP_INT_MAX],
                ['vormbyte_mcp_adapter_resources_list', 'vormbyte_read_mode_filter_mcp_list', PHP_INT_MAX],
                ['vormbyte_mcp_adapter_prompts_list', 'vormbyte_read_mode_filter_mcp_list', PHP_INT_MAX],
                ['wp_abilities_api_init', 'vormbyte_read_mode_reattach_guard_filter', PHP_INT_MIN],
                ['vormbyte_mcp_adapter_default_server_config', 'vormbyte_read_mode_default_server_config', PHP_INT_MAX],
                ['vormbyte_mcp_adapter_init', 'vormbyte_read_mode_reattach_server_config_filter', PHP_INT_MIN],
                ['vormbyte_mcp_adapter_pre_tool_call', 'vormbyte_read_mode_refuse_mcp_component', PHP_INT_MAX],
                ['vormbyte_mcp_adapter_pre_prompt_get', 'vormbyte_read_mode_refuse_mcp_component', PHP_INT_MAX],
                ['vormbyte_mcp_adapter_pre_resource_read', 'vormbyte_read_mode_refuse_mcp_component', PHP_INT_MAX],
                ['rest_request_before_callbacks', 'vormbyte_read_mode_rest_sweep', PHP_INT_MIN],
                ['vormbyte_mcp_adapter_enable_stdio_transport', 'vormbyte_read_mode_stdio_sweep', PHP_INT_MIN],
                ['admin_notices', 'vormbyte_render_read_mode_admin_notice', 10],
                ['network_admin_notices', 'vormbyte_render_read_mode_admin_notice', 10],
                ['added_option', 'Vormbyte\\ReadMode\\forget_state_on_option_change', 10],
                ['updated_option', 'Vormbyte\\ReadMode\\forget_state_on_option_change', 10],
                ['deleted_option', 'Vormbyte\\ReadMode\\forget_state_on_option_change', 10],
                ['w3tc_dbcache_can_cache_sql', 'Vormbyte\\ReadMode\\exclude_state_query_from_query_cache', PHP_INT_MAX],
                ['admin_menu', 'Vormbyte\\ReadMode\\register_menu', 91],
                ['admin_enqueue_scripts', 'Vormbyte\\ReadMode\\enqueue_page_assets', 10],
                ['admin_menu', 'Vormbyte\\ReadMode\\move_menu_entry', 999],
                ['admin_post_vormbyte_read_mode_disable', 'Vormbyte\\ReadMode\\handle_disable', 10],
            ],
            self::runScenario('hooks'),
        );
    }

    /**
     * Annotations are never used to decide the allowlist, but every allowlisted Ability must be
     * annotated read-only and require the Vormbyte management permission (or the adapter's own
     * discovery permission). Registered from Vormbyte's real files through the read-mode guard, every one
     * must also match the trusted implementation the allowlist is bound to.
     */
    public function testEveryAllowlistedAbilityIsAnnotatedReadOnlyAndTrusted(): void
    {
        $result = self::runScenario('annotations');

        foreach (self::BASE_ALLOWLIST as $name) {
            $ability = $result['abilities'][$name] ?? null;
            self::assertIsArray($ability, $name . ' was not registered.');
            // execute-ability keeps the adapter's annotations for a generic dispatcher, so its MCP tool is the
            // same in both modes; in read mode its permission refuses every target outside the allowlist.
            if ($name !== 'vormbyte-mcp-adapter/execute-ability') {
                self::assertTrue($ability['annotations']['readonly'] ?? null, $name . ' is not annotated readonly.');
                self::assertFalse(
                    $ability['annotations']['destructive'] ?? null,
                    $name . ' is not annotated non-destructive.',
                );
            }
            self::assertContains($ability['permission_callback'], [
                'vormbyte_permission_callback',
                'vormbyte_discover_abilities_permission',
                'vormbyte_get_ability_info_permission',
                'vormbyte_execute_ability_permission',
            ], $name);
            self::assertTrue($ability['guarded'], $name . ' has no read-mode guard.');
            self::assertTrue($ability['trusted'], $name . ' does not match its trusted implementation.');
        }
    }

    /**
     * @param list<string> $names
     * @return list<string>
     */
    private static function sorted(array $names): array
    {
        sort($names);
        return $names;
    }

    /** @return array<array-key, mixed> */
    private static function runScenario(string $scenario): array
    {
        $root = dirname(__DIR__, levels: 2);
        $command = [PHP_BINARY, $root . '/tests/fixtures/read-mode-policy-runner.php', $root, $scenario];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        self::assertSame(0, $exit_code, "stdout:\n{$stdout}\nstderr:\n{$stderr}");
        self::assertSame('', $stderr, $stderr);

        $decoded = json_decode($stdout, associative: true);
        self::assertIsArray($decoded, $stdout);
        self::assertArrayNotHasKey('unknown_scenario', $decoded);

        return $decoded;
    }
}
