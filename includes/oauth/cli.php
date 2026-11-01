<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\OAuth\Cli;

use Vormbyte\OAuth\Keys;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * WP-CLI entry point for the OAuth key bootstrap.
 *
 * Web PHP cannot always create the RSA key: on LocalWP under Windows the web process starts
 * without a usable OPENSSL_CONF and openssl_pkey_new() fails, which surfaces as a generic
 * authorization error. WP-CLI runs in a different, usually working PHP environment, so this
 * command is the supported way to create the keys once, without a hand-written bootstrap script.
 */
function register(): void
{
    if (!defined('WP_CLI') || constant('WP_CLI') !== true || !class_exists('WP_CLI')) {
        return;
    }

    \WP_CLI::add_command('vormbyte oauth-keys generate', __NAMESPACE__ . '\\generate_command');
}

/**
 * Create the Vormbyte OAuth keys if they do not exist yet, then verify they are usable.
 *
 * Reports existence and usability only. No key material and no encryption key is ever printed.
 *
 * ## EXAMPLES
 *
 *     wp vormbyte oauth-keys generate
 */
function generate_command(): void
{
    $existed = (string) get_option(Keys\PRIVATE_KEY_OPTION, default_value: '') !== '';

    try {
        $keys = Keys\get();
    } catch (\Throwable $e) {
        \WP_CLI::error(sprintf(
            /* translators: %s: underlying key generation error. */
            __('Vormbyte could not create its OAuth keys. %s', domain: 'vormbyte'),
            $e->getMessage(),
        ));

        return;
    }

    if (!keys_are_usable($keys['private']->getKeyContents(), $keys['public']->getKeyContents())) {
        \WP_CLI::error(__(
            'The stored Vormbyte OAuth private key exists but cannot sign. Delete the "vormbyte_oauth_private_key" option and run this command again.',
            domain: 'vormbyte',
        ));

        return;
    }

    \WP_CLI::success(
        $existed
            ? __('Vormbyte OAuth keys already existed and are usable.', domain: 'vormbyte')
            : __('Vormbyte OAuth keys created and verified as usable.', domain: 'vormbyte'),
    );
}

/**
 * Prove the pair works by signing and verifying a throwaway value. Neither key leaves this call.
 */
function keys_are_usable(string $private_pem, string $public_pem): bool
{
    if ($private_pem === '' || $public_pem === '') {
        return false;
    }

    $signature = '';
    if (!openssl_sign('vormbyte-oauth-key-check', $signature, $private_pem, OPENSSL_ALGO_SHA256)) {
        Keys\clear_openssl_errors();

        return false;
    }

    $verified = openssl_verify('vormbyte-oauth-key-check', $signature, $public_pem, OPENSSL_ALGO_SHA256);
    Keys\clear_openssl_errors();

    return $verified === 1;
}
