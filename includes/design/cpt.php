<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Design\Cpt;

if (!defined('ABSPATH')) {
    exit();
}

const POST_TYPE = 'vormbyte_design';

/** Option holding the active design slug. Empty string = none active. */
const OPTION_ACTIVE = 'vormbyte_active_design';

/** Post meta: who last wrote this design — 'agent' or 'user'. */
const META_LAST_ACTOR = '_design_last_actor';

function register(): void
{
    $capability = \vormbyte_manage_capability();

    register_post_type(POST_TYPE, [
        'label' => __('Designs', domain: 'vormbyte'),
        'public' => false,
        'show_ui' => false,
        'show_in_rest' => false,
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => ['vormbyte_design', 'vormbyte_designs'],
        'map_meta_cap' => true,
        'capabilities' => [
            'read' => $capability,
            'edit_posts' => $capability,
            'edit_others_posts' => $capability,
            'edit_private_posts' => $capability,
            'edit_published_posts' => $capability,
            'publish_posts' => $capability,
            'read_private_posts' => $capability,
            'delete_posts' => $capability,
            'delete_others_posts' => $capability,
            'delete_private_posts' => $capability,
            'delete_published_posts' => $capability,
            'create_posts' => $capability,
        ],
        'supports' => ['title', 'editor', 'revisions'],
    ]);
}
