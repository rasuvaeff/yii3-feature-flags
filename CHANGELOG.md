# Changelog

## 1.0.1 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
