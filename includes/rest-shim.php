<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Register a schema-agnostic POST runner for every valid slash-delimited Ability name.
 */
function vormbyte_register_ability_run_rest_shim(): void
{
    register_rest_route(
        route_namespace: 'vormbyte/v1',
        route: '/abilities/(?P<ability_name>[a-z0-9-]+(?:/[a-z0-9-]+)+)/run',
        args: [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'vormbyte_rest_run_ability',
            'permission_callback' => 'vormbyte_rest_run_ability_permission',
            'args' => [
                'ability_name' => [
                    'type' => 'string',
                    'required' => true,
                    'pattern' => '^[a-z0-9-]+(?:/[a-z0-9-]+)+$',
                ],
                // This documents the uniform body member without constraining its JSON value.
                // The callback validates the top-level object and rejects every other field.
                'input' => [
                    'required' => false,
                    'description' => 'Any JSON value passed to the target Ability. Omitted means null.',
                    'validate_callback' => '__return_true',
                ],
            ],
        ],
    );
}

/**
 * Enforce Vormbyte's enabled state and manage permission at the REST route boundary.
 */
function vormbyte_rest_run_ability_permission(): bool|WP_Error
{
    if (!vormbyte_is_enabled()) {
        return new WP_Error('vormbyte_disabled', __('Vormbyte AI Abilities are disabled.', domain: 'vormbyte'), [
            'status' => 403,
        ]);
    }

    if (!vormbyte_current_user_can_manage()) {
        return new WP_Error(
            'vormbyte_forbidden',
            __('You are not allowed to manage Vormbyte settings.', domain: 'vormbyte'),
            ['status' => 403],
        );
    }

    return true;
}

/**
 * Execute a REST-visible ability with body JSON shaped as `{ "input": <any JSON> }`.
 */
function vormbyte_rest_run_ability(WP_REST_Request $request): mixed
{
    if (function_exists('vormbyte_enforce_read_mode_registry')) {
        vormbyte_enforce_read_mode_registry();
    }

    if (!function_exists('wp_get_ability')) {
        return new WP_Error(
            'vormbyte_abilities_api_unavailable',
            __('The WordPress Abilities API is unavailable.', domain: 'vormbyte'),
            ['status' => 500],
        );
    }

    $ability_name = (string) $request['ability_name'];
    if (preg_match('#^[a-z0-9-]+(?:/[a-z0-9-]+)+$#', $ability_name) !== 1) {
        return new WP_Error('vormbyte_invalid_ability_name', __('Invalid Ability name.', domain: 'vormbyte'), [
            'status' => 400,
        ]);
    }

    // Checked at execution time, whatever the registry holds: an Ability registered after the read-mode
    // sweep is still refused.
    $read_mode_error = function_exists('vormbyte_read_mode_unavailable_ability_error')
        ? vormbyte_read_mode_unavailable_ability_error($ability_name)
        : null;
    if ($read_mode_error !== null) {
        return $read_mode_error;
    }

    $ability = wp_get_ability($ability_name);
    $untrusted = $ability instanceof WP_Ability && function_exists('vormbyte_read_mode_ability_refusal')
        ? vormbyte_read_mode_ability_refusal($ability)
        : null;
    if ($untrusted !== null) {
        return $untrusted;
    }
    if (!$ability instanceof WP_Ability) {
        return new WP_Error(
            'vormbyte_ability_not_found',
            sprintf(__('Ability not found: %s', domain: 'vormbyte'), $ability_name),
            ['status' => 404],
        );
    }

    if ($ability->get_meta_item('show_in_rest', false) !== true) {
        return new WP_Error(
            'vormbyte_ability_hidden',
            sprintf(__('Ability is not exposed through REST: %s', domain: 'vormbyte'), $ability_name),
            ['status' => 404],
        );
    }

    // @mago-expect analysis:mixed-assignment
    $input = vormbyte_rest_run_input($request);
    if ($input instanceof WP_Error) {
        return $input;
    }

    // @mago-expect analysis:mixed-assignment
    $result = $ability->execute($input);
    return $result instanceof WP_Error ? vormbyte_rest_classify_ability_error($result) : $result;
}

/**
 * Parse and validate the shim's strict top-level request object.
 */
function vormbyte_rest_run_input(WP_REST_Request $request): mixed
{
    $raw_body = trim($request->get_body());
    if ($raw_body === '') {
        return null;
    }
    if (!str_starts_with($raw_body, '{')) {
        return new WP_Error(
            'vormbyte_invalid_run_body',
            __(
                'The request body must be a JSON object containing only the optional "input" field.',
                domain: 'vormbyte',
            ),
            ['status' => 400],
        );
    }

    try {
        // Decode here as well as WordPress's parser so valid JSON null and malformed JSON remain
        // distinguishable in direct or internal dispatches.
        // @mago-expect analysis:mixed-assignment
        $body = json_decode($raw_body, associative: true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return new WP_Error('rest_invalid_json', __('Invalid JSON body passed.', domain: 'vormbyte'), [
            'status' => 400,
        ]);
    }
    if (!is_array($body)) {
        return new WP_Error(
            'vormbyte_invalid_run_body',
            __(
                'The request body must be a JSON object containing only the optional "input" field.',
                domain: 'vormbyte',
            ),
            ['status' => 400],
        );
    }

    $unexpected = array_values(array_diff(array_keys($body), ['input']));
    if ($unexpected !== []) {
        return new WP_Error(
            'vormbyte_unexpected_run_field',
            sprintf(
                __('Unexpected request field(s): %s.', domain: 'vormbyte'),
                implode(', ', array_map(static fn(int|string $field): string => (string) $field, $unexpected)),
            ),
            ['status' => 400, 'unexpected_fields' => $unexpected],
        );
    }

    /** @var mixed */
    return array_key_exists('input', $body) ? $body['input'] : null;
}

/**
 * Add an HTTP status only for stable WordPress Ability error classes; preserve code, message, and data.
 */
function vormbyte_rest_classify_ability_error(WP_Error $error): WP_Error
{
    $code = $error->get_error_code();
    $status = match ($code) {
        'ability_invalid_input', 'ability_missing_input_schema' => 400,
        'ability_invalid_permissions' => 403,
        default => null,
    };
    if ($status === null) {
        return $error;
    }

    // @mago-expect analysis:mixed-assignment
    $data = $error->get_error_data();
    if (is_array($data) && array_key_exists('status', $data)) {
        return $error;
    }
    $data = is_array($data) ? $data : [];
    $data['status'] = $status;

    return new WP_Error($code, $error->get_error_message(), $data);
}
