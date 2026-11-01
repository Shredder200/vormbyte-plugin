# Design-system smoke suite

Run against a real WordPress install (Abilities API in core) via wp-cli:

```sh
wp --path=/path/to/wp --user=admin eval-file tests/design-smoke/01-design-smoke.php
```

`--user=admin` is required: `vormbyte_permission_callback` needs
`manage_options`, and wp-cli runs as user 0 by default. The suite restores the
previously-active design and deletes its fixtures on exit; it exits non-zero if
any check fails.

Run every suite file for the full audit:

```sh
for suite in tests/design-smoke/*.php; do
    wp --path=/path/to/wp --user=admin eval-file "$suite" || exit 1
done
```

Coverage is split by concern:

- `01-design-smoke.php`: registration, contract activation gates, pre-flight
  rule semantics, declared overrides, direction non-inheritance, performance,
  revision retention, and reversible restore.
- `02-parser-audit.php`: real-world Markdown/front-matter forms, token parsing,
  provenance, guidance, dials, and schema readiness.
- `03-lifecycle-audit.php`: permission and input boundaries, save/get/list/
  activate/delete state, active token materialization, explicit check targets,
  stale active state, revision de-duplication and ownership, and inactive draft
  restore.

## Testing rule: a pre-flight trip is a skill bug

The pre-flight exists for the probabilistic residue: a tell that leaks from the
model even when the skill is used well. In testing, therefore, treat **every
trip as a bug of the skill to close at the source**, never as "the net worked"
— a trip during a test build means the brain or a realization skill routed
badly, and the fix belongs there, not in shipping the corrected output. Only at
ship time is the net doing its real job (catching the residue).
