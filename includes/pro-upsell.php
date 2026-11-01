<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

/**
 * Helpers for an optional Vormbyte Pro add-on. The commercial upsell surfaces of the
 * original plugin (submenu entry, Plugins-row link, welcome notice, Connect card) are removed.
 */

if (!defined('ABSPATH')) {
    exit();
}

/**
 * True when the Vormbyte Pro plugin is active.
 * License state is irrelevant — if Pro is running, the upsell is.
 */
function vormbyte_pro_is_active(): bool
{
    return defined('VORMBYTE_PRO_VERSION');
}

/**
 * Labels of the specializations whose plugin or theme is active on this site,
 * in published order.
 *
 * The list itself is not kept here: it is generated from the Pro manifest and
 * published with every Pro release (see includes/specializations.php), so this
 * plugin cannot fall behind the specializations Pro actually ships.
 *
 * @return list<string>
 */
function vormbyte_pro_active_integrations(): array
{
    $folders = vormbyte_active_folders();

    $active = [];
    foreach (vormbyte_specializations() as $specialization) {
        if (!vormbyte_specialization_is_active($specialization, $folders)) {
            continue;
        }
        $active[] = $specialization['label'];
    }
    return $active;
}

/**
 * The generic (no-match) copy: what Pro specializes in, said in groups rather
 * than by naming forty-odd products. Deliberately a plain sentence — the
 * published list always contains every group, so deriving this from it would
 * compute the same string every time.
 */
function vormbyte_pro_integration_groups(): string
{
    return __(
        'page builders, themes and block libraries, custom fields plugins, SEO plugins, form plugins, and more',
        domain: 'vormbyte',
    );
}

/**
 * One-line Pro upsell blurb, shared by the welcome notice and the Connect card.
 * Names the integrations the user already runs; falls back to the full grouped
 * catalog when none are detected.
 */
function vormbyte_pro_upsell_blurb(): string
{
    $active = vormbyte_pro_active_integrations();
    if ($active === []) {
        return sprintf(
            /* translators: %s: grouped list of integrations, e.g. "page builders (Elementor, Bricks), WooCommerce". */
            __(
                'Vormbyte Pro adds specializations that combine abilities and skills for %s, plus memory that persists between sessions.',
                domain: 'vormbyte',
            ),
            vormbyte_pro_integration_groups(),
        );
    }

    return sprintf(
        /* translators: %s: comma-separated list of detected plugin names, e.g. "Elementor, ACF, and WooCommerce". */
        __(
            'Vormbyte Pro adds specializations that combine abilities and skills for the tools you\'re already running (%s), plus memory that persists between sessions.',
            domain: 'vormbyte',
        ),
        wp_sprintf('%l', $active),
    );
}

/**
 * Kept as a no-op so callers on the Connect page keep working: there is no Pro product to promote.
 */
function vormbyte_render_pro_upsell_card(): void {}
