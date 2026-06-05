# AGENTS.md — yii3-feature-flags

Guidance for AI agents working on this package. Read before changing code.

## What this is

Feature flags, kill switches and percentage rollout for Yii3 applications.
Stateless core without DB/Redis dependencies. Deterministic rollout via SHA-256
hash. Namespace: `Rasuvaeff\Yii3FeatureFlags`.

Public API: `FeatureFlags` (facade), `Flag`, `FlagContext`, `FlagProvider`,
`ConfigFlagProvider`, `FlagRegistry`, `FlagEvaluator`, `PercentageRollout`,
`EvaluationResult`.

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
```

Or with Make:

```bash
make build
make cs:fix
make psalm
make test
```

`composer.lock` is gitignored (library).

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

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build` and paste the output.
