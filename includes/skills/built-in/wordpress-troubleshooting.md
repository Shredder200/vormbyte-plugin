---
name: wordpress-troubleshooting
description: Systematic diagnosis and repair of WordPress problems — critical errors and white screens, 500/404/redirect loops, changes not showing (caching), broken layouts and JavaScript, plugin/theme conflicts, slow pages, email not sending, cron, login and mixed-content issues. Load it when the user reports that something on their site is broken, slow, missing, or "not working", or after one of your own changes causes an error.
---

# Troubleshooting a WordPress site

Diagnose before fixing. Reproduce the problem, read the actual error, find the
cause, then make the smallest fix and verify. Do not "try things" on a live
site by deactivating plugins at random.

## 0. Reproduce and gather facts

- Which URL(s)? Logged in or out? Every visitor or only some (device, role,
  country)? Since when — what changed then (update, new plugin, your edit)?
- Fetch the URL yourself and record the HTTP status, final URL after redirects,
  and whether the HTML contains "There has been a critical error",
  `Fatal error`, `Warning`, or an empty body.

## 1. Read the errors

- PHP: `WP_DEBUG_LOG` path (`wp-content/debug.log` by default), the server
  error log (`ini_get( 'error_log' )`), and the recovery-mode email/`wp_fatal_error_handler`.
  To enable logging temporarily without showing errors to visitors:
  `WP_DEBUG=true`, `WP_DEBUG_LOG=true`, `WP_DEBUG_DISPLAY=false`
  (`wp config set WP_DEBUG true --raw` etc.). Turn it off again afterwards.
- `error_get_last()` after reproducing inside PHP, or run the failing code in a
  `try { … } catch ( \Throwable $e )`.
- JavaScript: if a browser is available, read the console. Otherwise check
  that enqueued script URLs return 200 and are not HTML error pages.
- Site Health: `WP_Site_Health` tests (`wp_get_update_data()`, loopback, REST
  availability, `wp cron test`).

The stack trace names the file: `wp-content/plugins/<slug>/…` or
`themes/<slug>/…` identifies the culprit directly. Read that code before
changing anything.

## 2. Common problems

**Critical error / white screen**
- Fatal from a plugin/theme after an update or edit. If you caused it, revert
  your change first. Otherwise, fix the code if it is the site's own code; for a
  third-party plugin, deactivate *only that plugin* (ask first on production)
  and tell the user. A Vormbyte sandbox file that crashes puts the sandbox in
  safe mode — fix the file and delete `.crashed`.
- Memory: "Allowed memory size exhausted" → find the leak/loop first; raising
  `WP_MEMORY_LIMIT` is a stopgap.

**500 errors without PHP errors** → `.htaccess` syntax, PHP version mismatch,
file permissions, or the web server log. Rebuild rules with `flush_rewrite_rules()`
only if WordPress' own block is broken; keep custom rules.

**404 on pages/posts that exist** → rewrite rules: re-save permalinks
(`wp rewrite flush`), check that the slug is not shared with an attachment or
term, and that a CPT was registered with `public`/`rewrite`.

**Redirect loops** → `home`/`siteurl` mismatch (http vs https, www vs non-www),
SSL behind a proxy (`$_SERVER['HTTPS']` not set — needs
`HTTP_X_FORWARDED_PROTO` handling in wp-config), a redirect plugin rule, or a
caching layer. Check `wp option get home` and `siteurl` first.

**Change not visible** → caching, almost always. Page cache plugin, server cache
(LiteSpeed, Varnish, Nginx FastCGI, host cache like Kinsta/WP Engine/SiteGround),
CDN (Cloudflare), builder CSS cache, object cache, browser cache. Purge in that
order and fetch with a cache-busting query string to compare. Also confirm you
edited the right layer (see the `wordpress-site-editing` skill).

**Layout broken / styles missing** → CSS/JS optimization (minify/combine/delay
JS in WP Rocket, Autoptimize, LiteSpeed, Perfmatters): exclude the affected file
or disable that optimization for the page; builder CSS not regenerated; mixed
content (http assets on https site → `wp search-replace 'http://domain' 'https://domain'`
with `--dry-run` first).

**Plugin/theme conflict** → reproduce with only the suspected plugin disabled
*for the test request* rather than site-wide when possible (e.g. a
`option_active_plugins` filter in an mu-plugin that only applies when a secret
query parameter is present, removed afterwards). On staging you may disable
plugins in halves to bisect. Never leave the site with plugins deactivated
without telling the user.

**Email not sending** → `wp_mail()` returns false or mail lands in spam: check
for an SMTP plugin and its log, test with `wp_mail( admin email, … )`, check the
`wp_mail_failed` action for the error. Hosts often block PHP mail; recommend
SMTP with SPF/DKIM.

**Scheduled posts/cron not running** → `DISABLE_WP_CRON`, loopback blocked,
`wp cron event list` for overdue events, `wp cron event run --due-now`.

**Slow pages** → measure first (time to first byte vs front-end). Query
Monitor if installed; count queries; look for uncached remote requests
(`pre_http_request` logging), huge autoloaded options
(`SELECT option_name, LENGTH(option_value) FROM wp_options WHERE autoload IN ('yes','on') ORDER BY 2 DESC LIMIT 20`),
expired transients, and missing object cache.

**Login problems** → cookies/`COOKIE_DOMAIN`, `home`/`siteurl`, security plugin
lockouts (Wordfence, Solid Security, Limit Login Attempts), a renamed login URL
(WPS Hide Login). Do not reset other users' passwords without asking.

**REST API / editor "Updating failed"** → security plugin or firewall blocking
`/wp-json/`, a PHP notice printed before JSON, or a plugin's REST error. Fetch
`/wp-json/` and the failing endpoint to see the real response.

## 3. Fix and verify

- Fix the cause, not the symptom (e.g. correct the code instead of hiding the
  warning). Keep a copy of anything you change.
- Re-run the reproduction from step 0 as a logged-out visitor, then check the
  debug log is clean of new errors.
- Revert temporary diagnostics: WP_DEBUG flags, test mu-plugins, test emails,
  disabled optimizations you only needed for the test.
- Report the cause, the fix, how to undo it, and any follow-up the user should
  do (e.g. "update plugin X", "ask the host to enable loopback requests").
