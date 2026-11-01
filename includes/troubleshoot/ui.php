<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Vormbyte\Troubleshoot\UI;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Render the troubleshooter panel: a run-checks report and a symptom picker with targeted
 * remedies. The Troubleshoot page is its single home (one canonical place to debug a connection),
 * and the Configuration page links there; $context prefixes the container id in case another host
 * ever needs it.
 *
 * $method scopes checks and symptoms to one connection method ('oauth' or 'password'). Pass ''
 * when the method is chosen at runtime — the Connect page keeps the attribute in sync with its
 * method chooser via window.vormbyteTsSetMethod().
 */
// The method picker is an opt-in rendering variant of the same panel, not a second responsibility.
// @mago-expect lint:no-boolean-flag-parameter
function render_panel(string $context, string $method = '', bool $with_method_picker = false): void
{
    $prefix = 'vormbyte-ts-' . sanitize_html_class($context);
    // Each symptom applies to one method (or both); the picker hides the rest.
    $symptoms = [
        'registration' => [
            'method' => 'oauth',
            'label' => __(
                '“Couldn’t register with [connection name]’s sign-in service. You can try again, or add an OAuth Client ID in the connector settings. If this persists, share this reference with support: ofid_…”',
                domain: 'vormbyte',
            ),
        ],
        'login-loop' => [
            'method' => 'oauth',
            'label' => __('The login page opens but authorizing loops or goes nowhere', domain: 'vormbyte'),
        ],
        'was-working' => [
            'method' => 'oauth',
            'label' => __('It worked before, now requests fail with 401', domain: 'vormbyte'),
        ],
        'no-tools' => [
            'method' => '',
            'label' => __('The client connects but shows no Vormbyte tools', domain: 'vormbyte'),
        ],
        'password-401' => [
            'method' => 'password',
            'label' => __('The Application Password method fails with 401', domain: 'vormbyte'),
        ],
    ];
    ?>
    <div
        class="vormbyte-troubleshoot"
        id="<?php echo esc_attr($prefix); ?>"
        data-vormbyte-ts-method="<?php echo esc_attr($method); ?>"
    >
        <div class="vormbyte-ts-controls">
            <?php if ($with_method_picker): ?>
                <label class="vormbyte-ts-method-pick">
                    <span class="vormbyte-ts-method-label"><?php esc_html_e(
                        'How do you connect?',
                        domain: 'vormbyte',
                    ); ?></span>
                    <select data-vormbyte-ts="method-pick">
                        <option value="" <?php selected($method, current: ''); ?>><?php esc_html_e(
                            'Not sure (check both)',
                            domain: 'vormbyte',
                        ); ?></option>
                        <option value="oauth" <?php selected($method, current: 'oauth'); ?>><?php esc_html_e(
                            'OAuth',
                            domain: 'vormbyte',
                        ); ?></option>
                        <option value="password" <?php selected($method, current: 'password'); ?>><?php esc_html_e(
                            'Application Password',
                            domain: 'vormbyte',
                        ); ?></option>
                    </select>
                </label>
            <?php endif; ?>
            <button type="button" class="button button-primary" data-vormbyte-ts="run"><?php esc_html_e(
                'Run diagnostics',
                domain: 'vormbyte',
            ); ?></button>
        </div>

        <div class="vormbyte-ts-summary" data-vormbyte-ts="summary" hidden></div>
        <div class="vormbyte-ts-clean" data-vormbyte-ts="clean" hidden></div>
        <ul class="vormbyte-ts-report" data-vormbyte-ts="report" aria-live="polite"></ul>
        <div class="vormbyte-ts-copy-report" data-vormbyte-ts="copy-report" hidden></div>

        <div class="vormbyte-ts-symptoms" data-vormbyte-ts="symptoms" hidden>
            <p class="vormbyte-ts-symptoms-title"><?php esc_html_e(
                'What do you see in your AI client?',
                domain: 'vormbyte',
            ); ?></p>
            <select data-vormbyte-ts="symptom">
                <option value=""><?php esc_html_e('Pick the closest symptom…', domain: 'vormbyte'); ?></option>
                <?php foreach ($symptoms as $key => $symptom): ?>
                    <option
                        value="<?php echo esc_attr($key); ?>"
                        data-method="<?php echo esc_attr($symptom['method']); ?>"
                    ><?php echo esc_html($symptom['label']); ?></option>
                <?php endforeach; ?>
            </select>

            <div class="vormbyte-ts-branch" data-vormbyte-ts-branch="registration" hidden>
                <p><?php esc_html_e(
                    'Adding an OAuth Client ID in the connector settings does not resolve this. That field expects an ID issued by this site, and providing one changes nothing about what is failing: the requests are refused before they reach WordPress. In our testing the cause is on the hosting or CDN layer every time, so the two options here are what restore the connection.',
                    domain: 'vormbyte',
                ); ?></p>

                <div class="vormbyte-ts-reset">
                    <p><?php esc_html_e(
                        'If the client reports too many attempts, its repeated tries have filled this site’s registration limits. Clearing them takes effect immediately and leaves connected clients untouched.',
                        domain: 'vormbyte',
                    ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field('vormbyte_troubleshoot_reset_limits'); ?>
                        <input type="hidden" name="vormbyte_action" value="reset_registration_limits">
                        <button type="submit" class="button"><?php esc_html_e(
                            'Reset registration limits',
                            domain: 'vormbyte',
                        ); ?></button>
                    </form>
                </div>

                <h3><?php esc_html_e('Choose the fix your AI client supports', domain: 'vormbyte'); ?></h3>
                <div class="vormbyte-ts-fix-options">
                    <div class="vormbyte-ts-fix-option is-recommended">
                        <h4><?php esc_html_e('Use Application Password', domain: 'vormbyte'); ?></h4>
                        <p><?php esc_html_e(
                            'Choose this when it is available for your AI client. Its local bridge avoids requests coming from the AI provider’s cloud servers.',
                            domain: 'vormbyte',
                        ); ?></p>
                        <a
                            class="button button-primary"
                            href="<?php echo esc_url(admin_url('admin.php?page=vormbyte-connect')); ?>"
                        ><?php esc_html_e('Set up Application Password', domain: 'vormbyte'); ?></a>
                    </div>
                    <div class="vormbyte-ts-fix-option">
                        <h4><?php esc_html_e('OAuth only? Contact hosting support', domain: 'vormbyte'); ?></h4>
                        <p><?php esc_html_e(
                            'Claude.ai only supports OAuth. Ask your hosting provider or CDN to allow the AI provider’s traffic to /.well-known/oauth-* and /wp-json/ without bot filtering.',
                            domain: 'vormbyte',
                        ); ?></p>
                        <details class="vormbyte-ts-support-email">
                            <summary><?php esc_html_e('Show email template', domain: 'vormbyte'); ?></summary>
                            <label><strong><?php esc_html_e('Subject', domain: 'vormbyte'); ?></strong>
                                <input
                                    type="text"
                                    class="large-text"
                                    data-vormbyte-ts="support-subject"
                                    value="<?php echo
                                        esc_attr__(
                                            'Request to allow AI client connections over the WordPress REST API',
                                            domain: 'vormbyte',
                                        )
                                    ; ?>"
                                />
                            </label>
                            <label><strong><?php esc_html_e('Message', domain: 'vormbyte'); ?></strong>
                                <textarea
                                    class="large-text code"
                                    rows="12"
                                    data-vormbyte-ts="support-message"
                                ><?php echo esc_textarea(\vormbyte_hosting_support_email('oauth')); ?></textarea>
                            </label>
                            <button
                                type="button"
                                class="button"
                                data-vormbyte-ts="support-copy"
                            ><?php esc_html_e('Copy email', domain: 'vormbyte'); ?></button>
                        </details>
                    </div>
                </div>
                <p class="vormbyte-ts-direct-design"><strong><?php esc_html_e(
                    'Why this can happen:',
                    domain: 'vormbyte',
                ); ?></strong> <?php esc_html_e(
                    'Vormbyte never routes the connection through its own servers. With cloud OAuth clients, your hosting sees the AI provider’s traffic directly and may mistake it for a bot.',
                    domain: 'vormbyte',
                ); ?></p>
            </div>

            <div class="vormbyte-ts-branch" data-vormbyte-ts-branch="login-loop" hidden>
                <p><?php esc_html_e(
                    'The authorization screen is a wp-admin page, so it needs the normal WordPress login cookie. If approving loops back to the login: finish the WordPress login in the same browser first, disable aggressive cookie/privacy extensions for this site, and if a maintenance or coming-soon plugin gates wp-admin, allow it for administrators.',
                    domain: 'vormbyte',
                ); ?></p>
            </div>

            <div class="vormbyte-ts-branch" data-vormbyte-ts-branch="was-working" hidden>
                <p><?php esc_html_e(
                    'A connection that stops with 401 usually means one of: the permalink structure changed (the token audience changes with it — most clients recover on their own at the next refresh, otherwise reconnect once), the access was revoked from Manage Connections, or the site moved to plain HTTP (which disables the OAuth endpoints entirely). The checks above cover the last case.',
                    domain: 'vormbyte',
                ); ?></p>
            </div>

            <div class="vormbyte-ts-branch" data-vormbyte-ts-branch="no-tools" hidden>
                <p><?php esc_html_e(
                    'The checks above already verify everything the server can see for this, including that AI Abilities are turned on. What the server cannot see is the client side: make sure the client points at the exact server URL from Step 4 — the OAuth URL and the Application Password URL are different endpoints — and restart the client so it reloads the server list.',
                    domain: 'vormbyte',
                ); ?></p>
            </div>

            <div class="vormbyte-ts-branch" data-vormbyte-ts-branch="password-401" hidden>
                <p><?php esc_html_e(
                    'For the Application Password method a 401 means: the password was revoked (check Manage Connections), Application Passwords are unavailable (see the checks above), the Authorization header is stripped before reaching PHP (the Configuration page shows a dedicated warning when it detects that), or a security plugin or firewall in front of the site is blocking or altering the authenticated request (see the Security & edge layers check above).',
                    domain: 'vormbyte',
                ); ?></p>
            </div>
        </div>
    </div>
    <?php

    render_assets_once();
}

/**
 * Styles + behavior, shared by every panel instance on the page (event delegation keyed on the
 * data attributes), printed once no matter how many hosts render a panel.
 */
function render_assets_once(): void
{
    static $printed = false;
    // @mago-expect analysis:impossible-condition
    if ($printed) {
        return;
    }
    $printed = true;
    $rest_nonce = (string) wp_create_nonce('wp_rest');
    $labels = [
        'ok' => __('OK', domain: 'vormbyte'),
        'warning' => __('Warning', domain: 'vormbyte'),
        'fail' => __('Problem', domain: 'vormbyte'),
        'skipped' => __('Skipped', domain: 'vormbyte'),
        'info' => __('Heads up', domain: 'vormbyte'),
        'allOk' => __('All checks passed', domain: 'vormbyte'),
        'clean' => __(
            'All automatic checks passed. This is necessary but not sufficient: an edge firewall or CDN can still block AI clients by IP or signature, which this probe cannot see from the server itself.',
            domain: 'vormbyte',
        ),
        'caveat' => __('Checks passed, with something to note', domain: 'vormbyte'),
        'error' => __('Could not run the checks. Reload the page and try again.', domain: 'vormbyte'),
        'copy' => __('Copy', domain: 'vormbyte'),
        'copied' => __('Copied!', domain: 'vormbyte'),
        'copyReport' => __('Copy report for support', domain: 'vormbyte'),
        'reportCopied' => __('Report copied', domain: 'vormbyte'),
        'openFix' => __('Open the fix below', domain: 'vormbyte'),
    ];
    ?>
    <style>
    .vormbyte-troubleshoot { max-width: 860px; }

    /* Report rows: one card per check, status told by the left rail + glyph chip, message in
       muted text, remedy in its own inset. Rows cascade in as the report renders. */
    .vormbyte-ts-report { list-style: none; margin: 0 0 4px; padding: 0; }
    .vormbyte-ts-row {
        display: flex; gap: 12px; margin: 0 0 8px; padding: 12px 16px 12px 13px;
        background: #fff; border: 1px solid #e5e5e7; border-left-width: 3px; border-radius: 6px;
        opacity: 0; transform: translateY(5px);
        animation: vormbyte-ts-in .3s cubic-bezier(.2, .7, .3, 1) forwards;
    }
    @keyframes vormbyte-ts-in { to { opacity: 1; transform: none; } }
    .vormbyte-ts-row.is-ok { border-left-color: #00a32a; }
    .vormbyte-ts-row.is-warning { border-left-color: #dba617; background: #fffdf3; }
    .vormbyte-ts-row.is-fail { border-left-color: #d63638; background: #fef8f8; }
    .vormbyte-ts-row.is-skipped { border-left-color: #c3c4c7; }
    .vormbyte-ts-row.is-info { border-left-color: #2271b1; background: #f0f6fc; }
    .vormbyte-ts-dot {
        flex: 0 0 auto; width: 22px; height: 22px; margin-top: 1px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; line-height: 1;
    }
    .is-ok .vormbyte-ts-dot { background: #edfaef; color: #00693e; }
    .is-warning .vormbyte-ts-dot { background: #fcf5e1; color: #996800; }
    .is-fail .vormbyte-ts-dot { background: #fcf0f1; color: #b32d2e; }
    .is-skipped .vormbyte-ts-dot { background: #f0f0f1; color: #646970; }
    .is-info .vormbyte-ts-dot { background: #e5f0fa; color: #135e96; }
    .vormbyte-ts-row-body { min-width: 0; }
    .vormbyte-ts-row-title { font-weight: 600; color: #1d2327; margin: 1px 0 2px; }
    .is-skipped .vormbyte-ts-row-title, .is-skipped .vormbyte-ts-row-msg { color: #787c82; }
    .vormbyte-ts-row-msg { color: #50575e; font-size: 13px; line-height: 1.55; overflow-wrap: break-word; }
    .vormbyte-ts-remedy {
        margin-top: 8px; padding: 8px 12px; border-radius: 4px;
        background: rgba(0, 0, 0, 0.04); font-size: 13px; line-height: 1.55; color: #3c434a;
    }
    .vormbyte-ts-remedy .button { display: inline-block; margin-top: 8px; }
    .vormbyte-ts-reset {
        margin: 12px 0 4px; padding: 12px 14px; border-radius: 6px;
        background: #fff; border: 1px solid #e5e5e7;
    }
    .vormbyte-ts-reset p { margin: 0 0 10px; font-size: 13px; line-height: 1.55; color: #50575e; }
    .vormbyte-ts-reset form { margin: 0; }
    .vormbyte-ts-copy { margin-top: 8px; }
    .vormbyte-ts-copy-text {
        margin: 0; padding: 10px 12px; border-radius: 4px;
        background: #fff; border: 1px solid #dcdcde;
        font-size: 12px; line-height: 1.5; color: #3c434a;
        white-space: pre-wrap; overflow-wrap: break-word;
    }

    /* Summary chips: the verdict at a glance before the row detail. */
    .vormbyte-ts-summary { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 12px; }
    .vormbyte-ts-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 13px;
        border-radius: 999px; font-size: 12px; font-weight: 600; letter-spacing: .01em;
    }
    .vormbyte-ts-chip.is-ok { background: #edfaef; color: #00693e; }
    .vormbyte-ts-chip.is-warning { background: #fcf5e1; color: #996800; }
    .vormbyte-ts-chip.is-fail { background: #fcf0f1; color: #b32d2e; }
    .vormbyte-ts-chip.is-skipped { background: #f0f0f1; color: #646970; }
    .vormbyte-ts-chip.is-info { background: #e5f0fa; color: #135e96; }
    .vormbyte-ts-clean { margin: 0 0 12px; padding: 12px 16px; border-radius: 6px;
        border: 1px solid #00a32a; background: #edfaef; color: #00693e; font-weight: 600; }
    .vormbyte-ts-clean.is-caveat { border-color: #dba617; background: #fffdf3; color: #8a6d0b; }
    .vormbyte-ts-copy-report { margin: 4px 0 12px; }

    /* Control row: method picker (optional) on the left, Run diagnostics on the right. */
    .vormbyte-ts-controls { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; margin: 0 0 14px; }
    .vormbyte-ts-method-pick { display: flex; flex-direction: column; gap: 4px; }
    .vormbyte-ts-method-label { font-weight: 600; color: #1d2327; }

    /* Skeleton shimmer while the probes run. */
    .vormbyte-ts-skeleton {
        height: 48px; margin: 0 0 8px; border-radius: 6px;
        background: linear-gradient(100deg, #f0f0f1 30%, #fafafa 45%, #f0f0f1 60%);
        background-size: 250% 100%;
        animation: vormbyte-ts-shimmer 1.1s ease-in-out infinite;
    }
    @keyframes vormbyte-ts-shimmer { from { background-position: 120% 0; } to { background-position: -30% 0; } }

    /* Symptom picker + remedy branches. */
    .vormbyte-ts-symptoms { border-top: 1px solid #e5e5e7; margin-top: 16px; padding-top: 14px; }
    .vormbyte-ts-symptoms-title { margin: 0 0 6px; font-weight: 600; color: #1d2327; }
    .vormbyte-ts-symptoms > select { min-width: min(420px, 100%); max-width: 100%; }
    .vormbyte-ts-branch {
        margin-top: 12px; padding: 14px 16px; border: 1px solid #dcdcde; border-radius: 6px;
        background: #f6f7f7;
    }
    .vormbyte-ts-branch > h3:first-child { margin-top: 0; }
    .vormbyte-ts-fix-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .vormbyte-ts-fix-option {
        padding: 14px 16px; border: 1px solid #dcdcde; border-radius: 5px; background: #fff;
    }
    .vormbyte-ts-fix-option.is-recommended { border-color: #2271b1; }
    .vormbyte-ts-fix-option h4 { margin: 0 0 6px; font-size: 14px; }
    .vormbyte-ts-fix-option p { margin: 0 0 12px; }
    .vormbyte-ts-support-email summary { cursor: pointer; font-weight: 600; color: #2271b1; }
    .vormbyte-ts-support-email label { display: block; margin: 12px 0; }
    .vormbyte-ts-support-email input,
    .vormbyte-ts-support-email textarea { display: block; margin-top: 4px; }
    .vormbyte-ts-support-email textarea { min-height: 220px; resize: vertical; }
    .vormbyte-ts-direct-design {
        margin: 12px 0 0; padding: 10px 12px; border-left: 3px solid #2271b1;
        background: #f0f6fc; color: #135e96;
    }
    @media (prefers-reduced-motion: reduce) {
        .vormbyte-ts-row { animation: none; opacity: 1; transform: none; }
        .vormbyte-ts-skeleton { animation: none; }
    }
    @media screen and (max-width: 782px) {
        .vormbyte-ts-fix-options { grid-template-columns: 1fr; }
    }
    </style>
    <script>
    (function () {
        var nonce = <?php echo wp_json_encode($rest_nonce); ?>;
        var runUrl = <?php echo wp_json_encode(rest_url('vormbyte/v1/troubleshoot/run-checks')); ?>;
        var labels = <?php echo wp_json_encode($labels); ?>;
        var glyphs = { ok: '✓', warning: '!', fail: '✕', skipped: '–', info: 'i' };

        // The panel now lives on its own Troubleshoot page, not only inside the Connect page, so it
        // must provide its own clipboard helper. navigator.clipboard needs a secure context (HTTPS
        // or localhost); on plain HTTP fall back to a hidden textarea + execCommand('copy').
        if (!window.vormbyteClipboardCopy) {
            window.vormbyteClipboardCopy = function (text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text);
                }
                return new Promise(function (resolve, reject) {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.top = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    var ok = false;
                    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                    document.body.removeChild(ta);
                    ok ? resolve() : reject(new Error('copy command was rejected'));
                });
            };
        }

        function post(url, body) {
            return window.fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                body: JSON.stringify(body || {})
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); });
        }

        function statusKey(status) {
            return Object.prototype.hasOwnProperty.call(glyphs, status) ? status : 'skipped';
        }

        function renderSkeleton(list) {
            list.textContent = '';
            for (var i = 0; i < 3; i++) {
                var li = document.createElement('li');
                li.className = 'vormbyte-ts-skeleton';
                list.appendChild(li);
            }
        }

        function renderSummary(summary, counts) {
            summary.textContent = '';
            ['fail', 'warning', 'info'].filter(function (key) { return counts[key] > 0; }).forEach(function (key) {
                var chip = document.createElement('span');
                chip.className = 'vormbyte-ts-chip is-' + key;
                chip.textContent = counts[key] + ' ' + labels[key];
                summary.appendChild(chip);
            });
            summary.hidden = false;
        }

        function buildRow(c, i) {
            var status = statusKey(c.status);
            var li = document.createElement('li');
            li.className = 'vormbyte-ts-row is-' + status;
            li.style.animationDelay = (i * 45) + 'ms';

            var dot = document.createElement('span');
            dot.className = 'vormbyte-ts-dot';
            dot.textContent = glyphs[status];
            dot.setAttribute('aria-label', labels[status] || status);
            li.appendChild(dot);

            var body = document.createElement('div');
            body.className = 'vormbyte-ts-row-body';
            var title = document.createElement('div');
            title.className = 'vormbyte-ts-row-title';
            title.textContent = c.label;
            body.appendChild(title);
            var msg = document.createElement('div');
            msg.className = 'vormbyte-ts-row-msg';
            msg.textContent = c.message;
            body.appendChild(msg);

            if (c.remedy) {
                var fix = document.createElement('div');
                fix.className = 'vormbyte-ts-remedy';
                fix.textContent = '→ ' + c.remedy;
                // A remedy that lives inside a symptom branch gets a button that opens it,
                // so nobody has to figure out which entry of the picker hides the fix.
                if (c.action) {
                    var open = document.createElement('button');
                    open.type = 'button';
                    open.className = 'button button-small';
                    open.textContent = labels.openFix;
                    open.setAttribute('data-vormbyte-ts-open', c.action);
                    fix.appendChild(document.createElement('br'));
                    fix.appendChild(open);
                }
                // A ready-to-send text (e.g. the message for the hosting support) rendered
                // as a copyable block right inside the remedy.
                if (c.copy) {
                    var copyWrap = document.createElement('div');
                    copyWrap.className = 'vormbyte-ts-copy';
                    var copyText = document.createElement('pre');
                    copyText.className = 'vormbyte-ts-copy-text';
                    copyText.textContent = c.copy;
                    copyWrap.appendChild(copyText);
                    var copyBtn = document.createElement('button');
                    copyBtn.type = 'button';
                    copyBtn.className = 'button button-small';
                    copyBtn.textContent = labels.copy;
                    copyBtn.setAttribute('data-vormbyte-ts', 'remedy-copy');
                    copyWrap.appendChild(copyBtn);
                    fix.appendChild(copyWrap);
                }
                body.appendChild(fix);
            }

            li.appendChild(body);
            return li;
        }

        function renderCopyReport(container, report) {
            container.textContent = '';
            if (!report) { container.hidden = true; return; }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'button';
            btn.textContent = labels.copyReport;
            btn.setAttribute('data-vormbyte-ts', 'report-copy');
            container.setAttribute('data-report', report);
            container.appendChild(btn);
            container.hidden = false;
        }

        // Outcome-driven: a clean run (all passed, nothing in front) shows one calm line; a run that
        // passed but detected a security/edge layer shows a caveat plus that row; a run with any
        // fail/warning shows only the problem rows. A healthy run stays quiet instead of listing
        // every green check, and the copy-report button appears whenever there is something to send.
        function renderReport(els, data) {
            var checks = data.checks || [];
            els.report.textContent = '';
            els.summary.hidden = true;
            els.summary.textContent = '';
            els.clean.hidden = true;
            els.clean.className = 'vormbyte-ts-clean';
            els.copyReport.hidden = true;
            els.copyReport.textContent = '';

            var problems = checks.filter(function (c) { return c.status === 'fail' || c.status === 'warning'; });
            var infos = checks.filter(function (c) { return c.status === 'info'; });

            if (problems.length === 0 && infos.length === 0) {
                els.clean.textContent = glyphs.ok + '  ' + labels.clean;
                els.clean.hidden = false;
                return;
            }

            if (problems.length === 0) {
                // Passed, but a security/edge layer is in front: a heads-up (amber), not a green pass.
                els.clean.className = 'vormbyte-ts-clean is-caveat';
                els.clean.textContent = labels.caveat;
                els.clean.hidden = false;
                infos.forEach(function (c, i) { els.report.appendChild(buildRow(c, i)); });
                renderCopyReport(els.copyReport, data.report);
                return;
            }

            problems.concat(infos).forEach(function (c, i) { els.report.appendChild(buildRow(c, i)); });
            var counts = { fail: 0, warning: 0, info: 0 };
            checks.forEach(function (c) { if (counts[c.status] !== undefined) { counts[c.status] += 1; } });
            renderSummary(els.summary, counts);
            renderCopyReport(els.copyReport, data.report);
        }

        // Clear a previous run's output. Used when the method changes, since the results, report,
        // and symptom guidance all belonged to the old method.
        function resetPanel(panel) {
            var report = panel.querySelector('[data-vormbyte-ts="report"]');
            var summary = panel.querySelector('[data-vormbyte-ts="summary"]');
            var clean = panel.querySelector('[data-vormbyte-ts="clean"]');
            var copyReport = panel.querySelector('[data-vormbyte-ts="copy-report"]');
            var symptoms = panel.querySelector('[data-vormbyte-ts="symptoms"]');
            var symptomSelect = panel.querySelector('[data-vormbyte-ts="symptom"]');
            if (report) { report.textContent = ''; }
            if (summary) { summary.hidden = true; summary.textContent = ''; }
            if (clean) { clean.hidden = true; clean.className = 'vormbyte-ts-clean'; }
            if (copyReport) { copyReport.hidden = true; copyReport.textContent = ''; }
            if (symptoms) { symptoms.hidden = true; }
            if (symptomSelect) { symptomSelect.value = ''; }
            panel.querySelectorAll('[data-vormbyte-ts-branch]').forEach(function (b) { b.hidden = true; });
        }

        function applyMethod(panel) {
            var method = panel.getAttribute('data-vormbyte-ts-method') || '';
            var select = panel.querySelector('[data-vormbyte-ts="symptom"]');
            if (!select) { return; }
            var selectedHidden = false;
            select.querySelectorAll('option[data-method]').forEach(function (option) {
                var own = option.getAttribute('data-method') || '';
                var show = method === '' || own === '' || own === method;
                option.hidden = !show;
                if (!show && option.selected) { selectedHidden = true; }
            });
            if (selectedHidden) {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // The Connect page calls this when its method chooser changes, so the panel's checks
        // and symptom list follow the method the user is actually setting up.
        window.vormbyteTsSetMethod = function (method) {
            document.querySelectorAll('.vormbyte-troubleshoot').forEach(function (panel) {
                panel.setAttribute('data-vormbyte-ts-method', method === 'oauth' || method === 'password' ? method : '');
                applyMethod(panel);
            });
        };

        document.querySelectorAll('.vormbyte-troubleshoot').forEach(applyMethod);

        document.addEventListener('click', function (e) {
            var target = e.target;
            if (!(target instanceof Element)) { return; }
            var panel = target.closest('.vormbyte-troubleshoot');
            if (!panel) { return; }

            var openSymptom = target.getAttribute('data-vormbyte-ts-open');
            if (openSymptom) {
                var symptomSelect = panel.querySelector('[data-vormbyte-ts="symptom"]');
                symptomSelect.value = openSymptom;
                symptomSelect.dispatchEvent(new Event('change', { bubbles: true }));
                var branch = panel.querySelector('[data-vormbyte-ts-branch="' + openSymptom + '"]');
                if (branch) { branch.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
                return;
            }

            var action = target.getAttribute('data-vormbyte-ts');
            if (!action) { return; }

            if (action === 'run') {
                var els = {
                    report: panel.querySelector('[data-vormbyte-ts="report"]'),
                    summary: panel.querySelector('[data-vormbyte-ts="summary"]'),
                    clean: panel.querySelector('[data-vormbyte-ts="clean"]'),
                    copyReport: panel.querySelector('[data-vormbyte-ts="copy-report"]')
                };
                var method = panel.getAttribute('data-vormbyte-ts-method') || '';
                els.summary.hidden = true;
                els.clean.hidden = true;
                els.copyReport.hidden = true;
                renderSkeleton(els.report);
                target.disabled = true;
                // Lock the method picker while a run is in flight, so it cannot change under a
                // response that belongs to the method the run started with.
                var methodPick = panel.querySelector('[data-vormbyte-ts="method-pick"]');
                if (methodPick) { methodPick.disabled = true; }
                post(runUrl, method === '' ? {} : { method: method }).then(function (r) {
                    target.disabled = false;
                    if (methodPick) { methodPick.disabled = false; }
                    if (!r.ok || !r.json.checks) { els.report.textContent = labels.error; return; }
                    renderReport(els, r.json);
                    // The symptom guide points at "the checks above", so it only appears once a run
                    // has produced them.
                    var symptoms = panel.querySelector('[data-vormbyte-ts="symptoms"]');
                    if (symptoms) { symptoms.hidden = false; }
                }).catch(function () {
                    target.disabled = false;
                    if (methodPick) { methodPick.disabled = false; }
                    els.report.textContent = labels.error;
                });
            }

            if (action === 'remedy-copy') {
                var block = target.closest('.vormbyte-ts-copy').querySelector('.vormbyte-ts-copy-text');
                window.vormbyteClipboardCopy(block.textContent).then(function () {
                    var origLabel = target.textContent;
                    target.textContent = labels.copied;
                    setTimeout(function () { target.textContent = origLabel; }, 1500);
                });
            }

            if (action === 'report-copy') {
                var reportEl = panel.querySelector('[data-vormbyte-ts="copy-report"]');
                window.vormbyteClipboardCopy(reportEl.getAttribute('data-report') || '').then(function () {
                    var orig = target.textContent;
                    target.textContent = labels.reportCopied;
                    setTimeout(function () { target.textContent = orig; }, 1500);
                });
            }

            if (action === 'support-copy') {
                var supportEmail = target.closest('.vormbyte-ts-support-email');
                var supportSubject = supportEmail.querySelector('[data-vormbyte-ts="support-subject"]').value;
                var supportMessage = supportEmail.querySelector('[data-vormbyte-ts="support-message"]').value;
                window.vormbyteClipboardCopy('Subject: ' + supportSubject + '\n\n' + supportMessage).then(function () {
                    var originalLabel = target.textContent;
                    target.textContent = labels.copied;
                    setTimeout(function () { target.textContent = originalLabel; }, 1500);
                });
            }

        });

        document.addEventListener('change', function (e) {
            var target = e.target;
            if (!(target instanceof Element)) { return; }
            var tsAttr = target.getAttribute('data-vormbyte-ts');
            if (tsAttr === 'method-pick') {
                if (window.vormbyteTsSetMethod) { window.vormbyteTsSetMethod(target.value); }
                var mpPanel = target.closest('.vormbyte-troubleshoot');
                if (mpPanel) { resetPanel(mpPanel); }
                return;
            }
            if (tsAttr !== 'symptom') { return; }
            var panel = target.closest('.vormbyte-troubleshoot');
            panel.querySelectorAll('[data-vormbyte-ts-branch]').forEach(function (branch) {
                branch.hidden = branch.getAttribute('data-vormbyte-ts-branch') !== target.value;
            });
        });
    })();
    </script>
    <?php
}
