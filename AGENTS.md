# AGENTS.md — yii3-feature-flags

Guidance for AI agents working on this package. Read before changing code.

## What this is

Feature flags, kill switches and percentage rollout for Yii3 applications.
Stateless core — storage backends are separate packages. Deterministic rollout via SHA-256
hash. Namespace: `Rasuvaeff\Yii3FeatureFlags`.

Public API: `FeatureFlags` (facade), `Flag`, `FlagContext`, `FlagProvider`,
`ConfigFlagProvider`, `FlagRegistry`, `FlagEvaluator`, `PercentageRollout`,
`EvaluationResult`.

Storage backends: `rasuvaeff/yii3-feature-flags-db` (database + caching),
`rasuvaeff/yii3-feature-flags-redis` (Redis HASH via Predis).

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Deterministic rollout.** Same `salt` + `subjectId` must always produce the
   same result. Changing `salt` is the only way to re-randomize.
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`composer.lock` is gitignored (library).
`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- Kill switch always overrides rollout, targeting and forced values.
- Rollout deterministic: `sha256(salt . ':' . subjectId)`, first 8 hex → bucket % 100.
- Flag name regex: `/^[a-z][a-z0-9._-]*$/`.
- Rollout percentage range: 0..100 inclusive.
- Unknown flag returns `false` in non-strict mode, throws in strict mode.
- User ID takes priority over tenant ID for rollout subject.
- Environment check only applies when flag has environments configured AND
  context provides an environment.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.

- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build`; if the change affects the public API or release
  process, also run `make release-check`. Paste the output.
