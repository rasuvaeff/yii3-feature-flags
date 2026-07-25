# Changelog

## 1.1.1 — 2026-07-25

- Reject trailing newlines in flag-name validation: anchor `Flag::NAME_PATTERN`
  with `\z` instead of `$` (PCRE `$` matches before a trailing `\n`, which let
  `"<name>\n"` pass and become a provider/storage key).

## 1.1.0 — 2026-07-25

- Ship an AI agent skill (`resources/skills/rasuvaeff-yii3-feature-flags/SKILL.md` +
  `extra.skills` in composer.json): projects using the `llm/skills` Composer
  plugin get the skill synced into `.agents/skills/` automatically on install.
- Bump `rasuvaeff/property-testing` dev dependency from `^1.0` to `^2.6`.
- Make property-test generator methods in `tests/PercentageRolloutTest.php`
  `public static` (rector's `RemoveUnusedPrivateMethodRector` deletes private
  methods that are only invoked via reflection).

## 1.0.2 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.

## 1.0.0 — 2026-06-14

- Initial stable release.
- `WritableFlagProvider` interface (`extends FlagProvider`): `save(Flag)`,
  `remove(string)`. Implemented by `rasuvaeff/yii3-feature-flags-db`.
- `EvaluationReason` string-backed enum: `Enabled`, `Disabled`, `KillSwitch`,
  `RolloutExcluded`, `EnvironmentExcluded`, `Forced`, `Unknown`.
- `EvaluationResult` rewrite: private constructor + 7 static factories +
  `getReason(): EvaluationReason`. The previous `isKillSwitchActive()` /
  `isRolloutExcluded()` / `isEnvironmentExcluded()` booleans are removed.
- `MetricsRecorder` interface + `NullMetricsRecorder` no-op. `FeatureFlags`
  accepts it as its last constructor argument and calls `recordEvaluation()`
  exactly once per `evaluate()` (never on the strict-mode throw path). Core DI
  does not bind `MetricsRecorder`.
- `Flag::validateRollout()` now throws `\InvalidArgumentException` (was
  `InvalidFlagNameException`). Name validation still throws
  `InvalidFlagNameException`, so callers can map form errors by exception type.

