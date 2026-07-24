---
name: rasuvaeff-yii3-feature-flags
description: >-
  Feature flags, kill switches and deterministic percentage rollout for Yii3
  with rasuvaeff/yii3-feature-flags — FeatureFlags facade, FlagContext,
  ConfigFlagProvider, FlagProvider, EvaluationResult/EvaluationReason,
  MetricsRecorder. Use when writing, reviewing or debugging feature-flag
  checks, rollout percentages, kill switches or flag DI wiring in a project
  that has this package installed.
---

# rasuvaeff/yii3-feature-flags

Stateless feature-flag core: a `FeatureFlags` facade evaluates flags against a
`FlagContext`; storage backends (e.g. `yii3-feature-flags-db`) are separate
packages. Namespace `Rasuvaeff\Yii3FeatureFlags\`.

## Safety rules — verify these on every change

1. **Core DI binds only `FeatureFlags`.** Never bind `FlagProvider` or
   `MetricsRecorder` in app config when a storage backend (`yii3-feature-flags-db`)
   is installed — the backend already binds it, and a second binding triggers a
   `yiisoft/config` `Duplicate key` error. Bind `FlagProvider` yourself only in a
   config-only setup with no backend.

2. **Kill switch overrides everything**, including context-forced values. A flag
   with `killSwitch: true` is off no matter what — never "fix" a stuck flag by
   forcing it; clear the kill switch.

3. **Rollout is deterministic**: `sha256(salt . ':' . subjectId)`, first 8 hex
   chars → bucket % 100. Same salt + subject always gives the same answer;
   changing the `salt` is the only way to re-randomize. User ID wins over tenant
   ID as the rollout subject.

4. **Unknown flags do not throw by default**: non-strict mode returns `false`
   (reason `Unknown`); `new FeatureFlags(provider: $p, strictMode: true)` throws
   `UnknownFlagException`. Don't rely on an exception to catch typos unless
   strict mode is on.

5. **Validation split**: flag names must match `/^[a-z][a-z0-9._-]*$/`
   (`InvalidFlagNameException`); rollout outside 0..100 throws plain
   `\InvalidArgumentException`. `EvaluationResult` is built only via its static
   factories — the constructor is private.

## Canonical usage

```php
use Rasuvaeff\Yii3FeatureFlags\{FeatureFlags, ConfigFlagProvider, FlagContext};

$provider = new ConfigFlagProvider(flags: [
    'new-checkout' => [
        'enabled' => true,
        'salt' => 'checkout-v1',
        'rollout' => 25,
        'environments' => ['production'],
    ],
]);

$ff = new FeatureFlags(provider: $provider);

$ff->isEnabled(flag: 'new-checkout', context: FlagContext::forUser(userId: 'user-42'));

$result = $ff->evaluate(flag: 'new-checkout', context: FlagContext::forUser(userId: 'user-42'));
$result->isEnabled();   // bool
$result->getReason();   // EvaluationReason enum (Enabled, KillSwitch, RolloutExcluded, ...)
```

## Full API

The complete reference — `Flag`/`FlagConfig` value objects, `FlagRegistry`,
`WritableFlagProvider`, all `EvaluationReason` cases, `MetricsRecorder` contract
and Yii3 config-plugin wiring — ships with the package: read
`vendor/rasuvaeff/yii3-feature-flags/llms.txt` before guessing a method name.
