<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Ability: Discover Abilities (Vormbyte replacement).
 *
 * Replaces the MCP Adapter's bundled discover-abilities tool so the response
 * includes Vormbyte's environment/usage instructions alongside the list of
 * abilities. These used to be sent via the MCP initialize handshake's
 * server_description, but some clients drop that; returning them here
 * guarantees the agent sees them on first tool discovery.
 */

if (!defined('ABSPATH')) {
    exit();
}

/** Keep the MCP Adapter's standard permission for the shared discovery tool. */
function vormbyte_discover_abilities_permission(): bool|\WP_Error
{
    if (function_exists('vormbyte_enforce_read_mode_registry')) {
        vormbyte_enforce_read_mode_registry();
    }

    if (!is_user_logged_in()) {
        return new \WP_Error('authentication_required', 'User must be authenticated to access this ability');
    }

    /** @var string $capability */
    $capability = apply_filters('vormbyte_mcp_adapter_discover_abilities_capability', value: 'read');
    if (!current_user_can($capability)) {
        return new \WP_Error('insufficient_capability', sprintf('User lacks required capability: %s', $capability));
    }

    return true;
}

/** Apply Vormbyte metadata visibility to the shared get-ability-info tool. */
function vormbyte_get_ability_info_permission(mixed $input = []): bool|\WP_Error
{
    if (function_exists('vormbyte_enforce_read_mode_registry')) {
        vormbyte_enforce_read_mode_registry();
    }

    $arguments = is_array($input) ? $input : [];
    $ability_name = is_string($arguments['ability_name'] ?? null) ? $arguments['ability_name'] : '';
    $read_mode_error = vormbyte_get_ability_info_read_mode_error($ability_name);
    if ($read_mode_error !== null) {
        return $read_mode_error;
    }

    $adapter_permission = \Vormbyte\Vendor\WP\MCP\Abilities\GetAbilityInfoAbility::check_permission($arguments);
    if (is_wp_error($adapter_permission) || !$adapter_permission) {
        return $adapter_permission;
    }

    if (!vormbyte_current_user_can_view_ability_metadata($ability_name)) {
        return new \WP_Error('insufficient_capability', 'You are not allowed to inspect Vormbyte ability metadata.');
    }

    return true;
}

/**
 * Permission for the shared execute-ability tool. In read mode it first refuses a target that is not a
 * trusted implementation of an allowlisted Ability, with the read-mode wording; otherwise, and outside
 * read mode, it is the adapter's own check.
 */
function vormbyte_execute_ability_permission(mixed $input = []): bool|\WP_Error
{
    $arguments = is_array($input) ? $input : [];
    if (function_exists('vormbyte_read_mode_execute_ability_refusal')) {
        $target = is_string($arguments['ability_name'] ?? null) ? $arguments['ability_name'] : '';
        $refusal = vormbyte_read_mode_execute_ability_refusal($target);
        if ($refusal !== null) {
            return $refusal;
        }
    }

    return \Vormbyte\Vendor\WP\MCP\Abilities\ExecuteAbilityAbility::check_permission($arguments);
}

/**
 * Discover the public tool Abilities and the Vormbyte instructions. In read mode only trusted
 * allowlisted Abilities are listed, with the read-mode guidance alone.
 *
 * @return array{vormbyte_instructions: string, abilities: list<array{name: string, label: string, description: string}>}
 */
function vormbyte_execute_discover_abilities(): array
{
    if (function_exists('vormbyte_enforce_read_mode_registry')) {
        vormbyte_enforce_read_mode_registry();
    }

    $ability_list = [];
    foreach (wp_get_abilities() as $ability) {
        $meta = $ability->get_meta();
        if (!($meta['mcp']['public'] ?? false)) {
            continue;
        }
        if (function_exists('vormbyte_read_mode_hides_ability') && vormbyte_read_mode_hides_ability($ability)) {
            continue;
        }
        if (($meta['mcp']['type'] ?? 'tool') !== 'tool') {
            continue;
        }
        if (!vormbyte_current_user_can_view_ability_metadata($ability->get_name())) {
            continue;
        }
        $ability_list[] = [
            'name' => $ability->get_name(),
            'label' => $ability->get_label(),
            'description' => $ability->get_description(),
        ];
    }

    $instructions = '';
    if (vormbyte_current_user_can_manage()) {
        $instructions = function_exists('vormbyte_agent_instructions')
            ? vormbyte_agent_instructions()
            : (string) apply_filters('vormbyte_discover_abilities_instructions', vormbyte_build_server_instructions());
    }

    return [
        'vormbyte_instructions' => $instructions,
        'abilities' => $ability_list,
    ];
}

/**
 * Explain an Ability that read mode removed to a user who manages Vormbyte. The adapter checks
 * permission before its result filters run, so its generic "not found" cannot be reworded later.
 */
function vormbyte_get_ability_info_read_mode_error(string $ability_name): ?\WP_Error
{
    if (!function_exists('vormbyte_is_read_mode') || !vormbyte_is_read_mode() || !vormbyte_current_user_can_manage()) {
        return null;
    }

    return vormbyte_read_mode_unavailable_ability_error($ability_name);
}

/** Register get-ability-info with the Vormbyte metadata visibility check. */
function vormbyte_protect_get_ability_info(): void
{
    $ability_name = 'vormbyte-mcp-adapter/get-ability-info';
    $ability = wp_has_ability($ability_name) ? wp_get_ability($ability_name) : null;
    $execute_callback = [\Vormbyte\Vendor\WP\MCP\Abilities\GetAbilityInfoAbility::class, 'execute'];
    if (!$ability instanceof \WP_Ability) {
        return;
    }

    wp_unregister_ability($ability_name);
    wp_register_ability($ability_name, [
        'label' => $ability->get_label(),
        'description' => $ability->get_description(),
        'category' => $ability->get_category(),
        'input_schema' => $ability->get_input_schema(),
        'output_schema' => $ability->get_output_schema(),
        'permission_callback' => 'vormbyte_get_ability_info_permission',
        'execute_callback' => $execute_callback,
        'meta' => $ability->get_meta(),
    ]);
}

/** Declare execute-ability parameters as an open object for strict MCP clients. */
function vormbyte_open_execute_ability_parameters(): void
{
    $ability_name = 'vormbyte-mcp-adapter/execute-ability';
    $ability = wp_has_ability($ability_name) ? wp_get_ability($ability_name) : null;
    if (!$ability instanceof \WP_Ability) {
        return;
    }

    $input_schema = $ability->get_input_schema();
    /** @var array<string, mixed>|null $properties */
    $properties = $input_schema['properties'] ?? null;
    if (!is_array($properties)) {
        return;
    }

    /** @var array<string, mixed>|null $parameters_schema */
    $parameters_schema = $properties['parameters'] ?? null;
    if (!is_array($parameters_schema)) {
        return;
    }

    // WordPress expects schemas as arrays. vormbyte_normalize_empty_schema_properties(), called by
    // vormbyte.php's rest_pre_echo_response filter, converts this empty map to a JSON object.
    $parameters_schema['properties'] = [];
    $parameters_schema['additionalProperties'] = true;
    $properties['parameters'] = $parameters_schema;
    $input_schema['properties'] = $properties;

    wp_unregister_ability($ability_name);
    wp_register_ability($ability_name, [
        'label' => $ability->get_label(),
        'description' => $ability->get_description(),
        'category' => $ability->get_category(),
        'input_schema' => $input_schema,
        'output_schema' => $ability->get_output_schema(),
        'permission_callback' => 'vormbyte_execute_ability_permission',
        'execute_callback' => [\Vormbyte\Vendor\WP\MCP\Abilities\ExecuteAbilityAbility::class, 'execute'],
        'meta' => $ability->get_meta(),
    ]);
}

/** Recursively make empty JSON Schema property maps encode as JSON objects. */
function vormbyte_normalize_empty_schema_properties(mixed $schema): mixed
{
    if (is_array($schema)) {
        return vormbyte_normalize_empty_schema_property_array($schema);
    }

    if ($schema instanceof \stdClass) {
        return (object) vormbyte_normalize_empty_schema_property_array((array) $schema);
    }

    return $schema;
}

/**
 * @param array<array-key, mixed> $schema
 * @return array<array-key, mixed>
 */
function vormbyte_normalize_empty_schema_property_array(array $schema): array
{
    // @mago-expect analysis:mixed-assignment -- JSON Schema values are intentionally heterogeneous.
    foreach ($schema as $key => $value) {
        if (
            is_string($key)
            && in_array(
                $key,
                ['properties', 'additionalProperties', 'patternProperties', '$defs', 'definitions'],
                strict: true,
            )
            && $value === []
        ) {
            $schema[$key] = new \stdClass();
            continue;
        }

        $schema[$key] = vormbyte_normalize_empty_schema_properties($value);
    }
    return $schema;
}

$vormbyte_existing_ability = wp_has_ability('vormbyte-mcp-adapter/discover-abilities')
    ? wp_get_ability('vormbyte-mcp-adapter/discover-abilities')
    : null;
if ($vormbyte_existing_ability !== null) {
    wp_unregister_ability('vormbyte-mcp-adapter/discover-abilities');
}

if (wp_has_ability('vormbyte-mcp-adapter/discover-abilities')) {
    return;
}

wp_register_ability('vormbyte-mcp-adapter/discover-abilities', [
    'label' => __('Discover Abilities', domain: 'vormbyte'),
    'description' => __(
        'Discover all available WordPress abilities in the system. Returns a list of all registered abilities with their basic information, plus Vormbyte environment instructions.',
        domain: 'vormbyte',
    ),
    'category' => 'vormbyte-mcp-adapter',
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'vormbyte_instructions' => [
                'type' => 'string',
                'description' => 'Vormbyte environment and usage guidance for the agent.',
            ],
            'abilities' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'label' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                    ],
                    'required' => ['name', 'label', 'description'],
                ],
            ],
        ],
        'required' => ['vormbyte_instructions', 'abilities'],
    ],
    'permission_callback' => 'vormbyte_discover_abilities_permission',
    'execute_callback' => 'vormbyte_execute_discover_abilities',
    'meta' => [
        'annotations' => [
            'readonly' => true,
            'destructive' => false,
            'idempotent' => true,
        ],
        'mcp' => [
            'public' => true,
            'type' => 'tool',
        ],
    ],
]);

vormbyte_protect_get_ability_info();
vormbyte_open_execute_ability_parameters();
