# REST Ability Contract v1 Fixtures

These fixtures freeze the server contract consumed by `@vormbyte/cli` 1.x.
The full normative contract is in `../../../../vormbyte-cli/docs/v1-contract.md`.
The REST-only Phase 1 integration path and fixed CLI server floor are documented
in [`SERVER_CONTRACT.md`](SERVER_CONTRACT.md).
The JSON files must remain byte-identical to
`../../../../vormbyte-cli/fixtures/v1`.

The first compatible server is WordPress 6.9 with Vormbyte 1.11.0, REST API
contract `1`, and all of these feature values strictly `true`:

```text
abilities_bearer_auth
agent_context
rest_skills
generalized_execution_shim
```

Protected-resource metadata publishes the compatibility block as `vormbyte`;
`vormbyte/agent-context` returns the identical block as `server`.

The v1 OAuth audience remains `rest_url('mcp/vormbyte-oauth')`. The single
advertised `mcp` scope authorizes both the MCP endpoint and every
otherwise-permitted REST-visible Ability. Former CLI scopes `abilities` and
`abilities:read` remain accepted as full-access upgrade aliases for
already-issued tokens.

Full access intentionally includes third-party abilities with
`meta.show_in_rest: true`; consent must state that compatible third-party
plugin abilities can execute code, modify files/content or settings, and
create temporary administrator access. Normal Vormbyte, user-capability,
`show_in_rest`, and target permission checks remain authoritative.

The Bearer route allowlist is only:

```text
GET|HEAD /wp-abilities/v1/abilities
GET      /wp-abilities/v1/abilities/{complete-name}
POST     /vormbyte/v1/abilities/{complete-name}/run
```

The shim accepts exactly `{}` (meaning `input: null`) or
`{"input": <any JSON value>}` and preserves raw successful JSON values. It
rejects malformed JSON, scalar/array top-level bodies, and unexpected fields
with a WordPress REST error at status 400. Complete validated slash-delimited
Ability names are supported.
