<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

// Design lifecycle audit: ability validation and permissions, normalized slug
// semantics, list/get/activate/delete state, active tokens, revision
// de-duplication and ownership, and incomplete inactive restores.
// Runs against a real WP install via wp-cli and deletes its fixtures on exit.

use Vormbyte\Design\Contract;
use Vormbyte\Design\Cpt;
use Vormbyte\Design\Library;
use Vormbyte\Design\Parser;
use Vormbyte\Design\Revisions;
use Vormbyte\Design\Store;

if (!defined('ABSPATH')) {
    exit('Run from wp-cli only');
}

$admin = get_user_by('login', 'admin');
if ($admin) {
    wp_set_current_user($admin->ID);
} else {
    $admins = get_users(['role' => 'administrator', 'number' => 1]);
    if (!empty($admins)) {
        wp_set_current_user($admins[0]->ID);
    }
}

global $RESULTS;
$RESULTS = [];

function check(string $name, bool $pass, string $detail = ''): void
{
    global $RESULTS;
    $RESULTS[] = ($pass ? 'PASS' : 'FAIL') . " | $name" . ($detail !== '' ? " | $detail" : '');
}

function ab(string $slug, array $input = []): mixed
{
    $ability = wp_get_ability($slug);
    if ($ability === null) {
        return new WP_Error('missing', "$slug not registered");
    }
    return $ability->execute($input);
}

function is_error_code(mixed $result, string $code): bool
{
    return is_wp_error($result) && $result->get_error_code() === $code;
}

function design_document(string $name, string $accent, string $overview, bool $allow_em_dash = false): string
{
    $allow = $allow_em_dash ? "allow: [em-dash]\n" : '';
    return "---\n"
        . "name: \"$name\"\n"
        . "description: \"Lifecycle fixture\"\n"
        . "colors:\n"
        . "  bg: \"#f7f7f2\"\n"
        . "  ink: \"#171a18\"\n"
        . "  accent: \"$accent\"\n"
        . "typography:\n"
        . "  heading:\n"
        . "    fontFamily: \"Cabinet Grotesk, sans-serif\"\n"
        . "  body:\n"
        . "    fontFamily: \"General Sans, sans-serif\"\n"
        . "spacing:\n"
        . "  md: 16px\n"
        . "rounded:\n"
        . "  md: 6px\n"
        . "components:\n"
        . "  buttons: Solid accent\n"
        . "dials:\n"
        . "  variance: 0.4\n"
        . "  density: 0.35\n"
        . "  motion: 0.15\n"
        . $allow
        . "---\n\n"
        . "# $name\n\n"
        . "## Overview\n$overview\n";
}

$previous_active = Store\get_active_slug();
$run = substr(str_replace('-', '', wp_generate_uuid4()), offset: 0, length: 8);
$primary_input_slug = "Smoke Lifecycle $run";
$primary_slug = Parser\normalize_slug($primary_input_slug);
$secondary_slug = "smoke-secondary-$run";
$incomplete_slug = "smoke-inactive-draft-$run";
$blocked_slug = "smoke-blocked-$run";
$foreign_slug = "smoke-foreign-$run";
$fixture_slugs = [$primary_slug, $secondary_slug, $incomplete_slug, $blocked_slug, $foreign_slug];

// ---------- 1. Ability boundary ----------
$admin_id = get_current_user_id();
wp_set_current_user(0);
$result = ab('vormbyte/list-design-library');
check('abilities reject a user without the management capability', is_error_code($result, 'ability_invalid_permissions'));
wp_set_current_user($admin_id);

$result = ab('vormbyte/save-design');
check('ability input schema rejects a missing required field', is_error_code($result, 'ability_invalid_input'));

$result = ab('vormbyte/save-design', ['content' => "---\nname: Broken\n# missing closing fence"]);
check('save-design rejects malformed front matter', is_error_code($result, 'invalid_design'));

$result = ab('vormbyte/save-design', ['content' => str_repeat('x', Parser\MAX_BYTES + 1)]);
check('save-design rejects content beyond the 1 MB boundary', is_error_code($result, 'too_large'));

// ---------- 2. Save, get, list, activate ----------
$primary_v1 = design_document('Lifecycle Primary', '#0f6b4f', 'Version one.', allow_em_dash: true);
$result = ab('vormbyte/save-design', [
    'slug' => $primary_input_slug,
    'content' => $primary_v1,
]);
$primary_post = Store\find_user_post($primary_slug);
check(
    'save-design normalizes its explicit slug and records agent provenance',
    !is_wp_error($result)
    && ($result['slug'] ?? '') === $primary_slug
    && $primary_post instanceof WP_Post
    && get_post_meta($primary_post->ID, Cpt\META_LAST_ACTOR, true) === 'agent',
    is_wp_error($result) ? $result->get_error_code() : json_encode(['slug' => $result['slug'] ?? null]),
);

$initial_revision_count = $primary_post instanceof WP_Post ? count(wp_get_post_revisions($primary_post->ID)) : -1;
ab('vormbyte/save-design', ['slug' => $primary_slug, 'content' => $primary_v1]);
$primary_post = Store\find_user_post($primary_slug);
$same_revision_count = $primary_post instanceof WP_Post ? count(wp_get_post_revisions($primary_post->ID)) : -1;
check(
    'saving identical content does not create a duplicate revision',
    $initial_revision_count === 1 && $same_revision_count === 1,
    json_encode(['initial' => $initial_revision_count, 'after_noop' => $same_revision_count]),
);

$primary_v2 = str_replace('Version one.', 'Version two.', $primary_v1);
ab('vormbyte/save-design', ['slug' => $primary_slug, 'content' => $primary_v2]);
$primary_post = Store\find_user_post($primary_slug);
check(
    'changing content creates exactly one additional revision',
    $primary_post instanceof WP_Post && count(wp_get_post_revisions($primary_post->ID)) === 2,
    $primary_post instanceof WP_Post ? (string) count(wp_get_post_revisions($primary_post->ID)) : 'post missing',
);

$result = ab('vormbyte/get-design', ['slug' => $primary_input_slug]);
check(
    'get-design accepts a non-canonical slug and returns the canonical record',
    !is_wp_error($result)
    && ($result['found'] ?? false)
    && ($result['slug'] ?? '') === $primary_slug
    && ($result['content'] ?? '') === $primary_v2
    && !($result['is_active'] ?? true)
    && ($result['readiness']['sync_ready'] ?? false),
    is_wp_error($result) ? $result->get_error_code() : json_encode(['slug' => $result['slug'] ?? null]),
);

$result = ab('vormbyte/get-design', ['slug' => "missing-$run"]);
check('get-design reports an unknown design without an error', !is_wp_error($result) && $result === ['found' => false]);

$result = ab('vormbyte/list-design-library');
$listed_primary = [];
if (!is_wp_error($result)) {
    $listed_primary = array_values(array_filter(
        $result['designs'] ?? [],
        static fn(array $record): bool => ($record['slug'] ?? '') === $primary_slug,
    ));
}
check(
    'list-design-library exposes one ready, sync-ready record',
    count($listed_primary) === 1
    && ($listed_primary[0]['ready'] ?? false)
    && ($listed_primary[0]['sync_ready'] ?? false),
    json_encode($listed_primary),
);

$result = ab('vormbyte/activate-design', ['slug' => "missing-$run"]);
check('activate-design rejects an unknown slug', is_error_code($result, 'unknown_design'));

$result = ab('vormbyte/activate-design', ['slug' => $primary_input_slug]);
$active = ab('vormbyte/get-active-design');
check(
    'activate-design normalizes the slug and get-active-design reflects persisted state',
    !is_wp_error($result)
    && ($result['slug'] ?? '') === $primary_slug
    && !is_wp_error($active)
    && ($active['active'] ?? false)
    && ($active['slug'] ?? '') === $primary_slug,
    is_wp_error($result) ? $result->get_error_code() : json_encode(['active' => $active]),
);

$tokens = Library\active_tokens();
check(
    'active_tokens returns canonical sync values and provenance',
    $tokens !== null
    && ($tokens['colors']['accent'] ?? '') === '#0f6b4f'
    && ($tokens['fonts']['heading'] ?? '') === 'Cabinet Grotesk, sans-serif'
    && ($tokens['token_sources']['colors'] ?? '') === 'explicit'
    && ($tokens['readiness']['sync_ready'] ?? false),
    json_encode($tokens),
);

// ---------- 3. Explicit check target ----------
$secondary = design_document('Lifecycle Secondary', '#0047ab', 'Secondary direction.');
ab('vormbyte/save-design', ['slug' => $secondary_slug, 'content' => $secondary]);
$with_active = ab('vormbyte/check-design', ['output' => '<p>Allowed — by primary.</p>']);
$with_explicit = ab('vormbyte/check-design', [
    'slug' => $secondary_slug,
    'output' => '<p>Rejected — by secondary.</p>',
]);
check(
    'check-design uses an explicit design instead of inheriting the active one',
    !is_wp_error($with_active)
    && ($with_active['ok'] ?? false)
    && ($with_active['slug'] ?? '') === $primary_slug
    && !is_wp_error($with_explicit)
    && !($with_explicit['ok'] ?? true)
    && ($with_explicit['slug'] ?? '') === $secondary_slug,
    json_encode(['active' => $with_active, 'explicit' => $with_explicit]),
);

$result = ab('vormbyte/check-design', ['slug' => "missing-$run", 'output' => '<p>Candidate</p>']);
check('check-design rejects an explicitly unknown design', is_error_code($result, 'unknown_design'));

// ---------- 4. Contract gate and stale state ----------
$incomplete_active = "---\nname: Incomplete Draft\ncolors:\n  bg: '#ffffff'\n  ink: '#111111'\n---\n# Incomplete Draft\n";
$result = ab('vormbyte/save-design', ['slug' => $blocked_slug, 'content' => $incomplete_active, 'activate' => true]);
check(
    'an incomplete design is saved for repair but cannot be activated',
    !is_wp_error($result)
    && ($result['saved'] ?? false)
    && !($result['activated'] ?? true)
    && ($result['activation_blocked'] ?? false)
    && !($result['readiness']['ready'] ?? true)
    && Store\get_active_slug() === $primary_slug,
    is_wp_error($result) ? $result->get_error_code() : json_encode($result['readiness'] ?? null),
);

// A complete DESIGN.md from another implementation, carrying unknown foreign
// keys, imports end to end: save-design stores and activates it.
$foreign_import = "---\nschema_version: 7\nvendor: acme-designer\nname: Foreign Import\ncolors:\n  bg: '#ffffff'\n  ink: '#111111'\n  accent: '#0f6b4f'\ntypography:\n  heading: Inter\n  body: Inter\n---\n# Foreign Import\n";
$result = ab('vormbyte/save-design', ['slug' => $foreign_slug, 'content' => $foreign_import, 'activate' => true]);
check(
    'a foreign DESIGN.md with unknown keys saves and activates through save-design',
    !is_wp_error($result)
    && ($result['saved'] ?? false)
    && ($result['activated'] ?? false)
    && ($result['readiness']['ready'] ?? false)
    && Store\get_active_slug() === $foreign_slug,
    is_wp_error($result) ? $result->get_error_code() : json_encode($result['readiness'] ?? null),
);
Store\set_active($primary_slug);

Store\set_active("stale-$run");
$stale_active = ab('vormbyte/get-active-design');
check(
    'a stale active option fails closed instead of exposing phantom tokens',
    !is_wp_error($stale_active) && $stale_active === ['active' => false] && Library\active_tokens() === null,
    json_encode($stale_active),
);
Store\set_active($primary_slug);

// ---------- 5. Revision edge cases ----------
check(
    'revision retention respects disabled, stricter, and unlimited site settings',
    $primary_post instanceof WP_Post
    && Revisions\limit(0, $primary_post) === 0
    && Revisions\limit(3, $primary_post) === 3
    && Revisions\limit(-1, $primary_post) === Revisions\MAX_REVISIONS
    && Revisions\limit(20, $primary_post) === Revisions\MAX_REVISIONS,
);

$secondary_post = Store\find_user_post($secondary_slug);
$secondary_revisions = $secondary_post instanceof WP_Post ? wp_get_post_revisions($secondary_post->ID) : [];
$foreign_revision = reset($secondary_revisions);
check(
    'a revision from another design cannot be resolved as restorable',
    $primary_post instanceof WP_Post
    && $foreign_revision instanceof WP_Post
    && Revisions\find($primary_post, $foreign_revision->ID) === null,
);

$incomplete = "---\nname: Inactive Draft\ncolors:\n  bg: '#ffffff'\n---\n# Inactive Draft\n";
ab('vormbyte/save-design', ['slug' => $incomplete_slug, 'content' => $incomplete]);
$repaired = design_document('Inactive Draft', '#b4603a', 'Repaired and ready.');
ab('vormbyte/save-design', ['slug' => $incomplete_slug, 'content' => $repaired]);
$incomplete_post = Store\find_user_post($incomplete_slug);
$draft_revision = null;
if ($incomplete_post instanceof WP_Post) {
    foreach (Revisions\history($incomplete_post) as $candidate) {
        if (!Contract\inspect($candidate->post_content)['readiness']['ready']) {
            $draft_revision = $candidate;
            break;
        }
    }
}
$restore = $incomplete_post instanceof WP_Post && $draft_revision instanceof WP_Post
    ? Revisions\restore($incomplete_post, $draft_revision, actor: 'user')
    : new WP_Error('missing_fixture');
$incomplete_post = Store\find_user_post($incomplete_slug);
check(
    'an inactive design may restore an incomplete draft and records user provenance',
    !is_wp_error($restore)
    && $incomplete_post instanceof WP_Post
    && $incomplete_post->post_content === $incomplete
    && get_post_meta($incomplete_post->ID, Cpt\META_LAST_ACTOR, true) === 'user'
    && !Contract\inspect($incomplete_post->post_content)['readiness']['ready'],
    is_wp_error($restore) ? $restore->get_error_code() : 'restored',
);
$result = ab('vormbyte/activate-design', ['slug' => $incomplete_slug]);
check('a restored incomplete draft remains non-activatable', is_error_code($result, 'design_not_ready'));

// ---------- 6. Delete and cleanup ----------
$primary_id = $primary_post instanceof WP_Post ? $primary_post->ID : 0;
$result = ab('vormbyte/delete-design', ['slug' => $primary_input_slug]);
check(
    'deleting the active design returns its canonical slug and clears active state',
    !is_wp_error($result)
    && ($result['deleted'] ?? false)
    && ($result['slug'] ?? '') === $primary_slug
    && ($result['was_active'] ?? false)
    && Store\get_active_slug() === ''
    && Store\find_user_post($primary_slug) === null
    && ($primary_id === 0 || wp_get_post_revisions($primary_id) === []),
    is_wp_error($result) ? $result->get_error_code() : json_encode($result),
);

$result = ab('vormbyte/delete-design', ['slug' => $primary_slug]);
check('deleting the same design twice reports unknown_design', is_error_code($result, 'unknown_design'));

foreach ($fixture_slugs as $slug) {
    Store\delete($slug);
}
Store\set_active($previous_active);
check('cleanup restores the previous active design', Store\get_active_slug() === $previous_active);

// ---------- Report ----------
$failed = count(array_filter($RESULTS, static fn(string $line): bool => str_starts_with($line, 'FAIL')));
echo implode("\n", $RESULTS) . "\n";
echo sprintf("lifecycle-audit: %d checks, %d failed\n", count($RESULTS), $failed);
if ($failed > 0) {
    exit(1);
}
