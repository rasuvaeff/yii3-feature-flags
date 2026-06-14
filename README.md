# rasuvaeff/yii3-feature-flags

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-feature-flags.svg?label=stable)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-feature-flags.svg)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-feature-flags/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-feature-flags/actions)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-feature-flags/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-feature-flags/actions)
[![Psalm Level](https://img.shields.io/badge/Psalm-1-blue.svg)](https://github.com/rasuvaeff/yii3-feature-flags/actions)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-feature-flags/php)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-feature-flags.svg)](LICENSE.md)

Feature flags, kill switches and percentage rollout for Yii3 applications.

Stateless core — storage backends are separate packages. Deterministic rollout via
SHA-256 hash. Works with Yii3 config-plugin or standalone.

> Using an AI coding assistant? [llms.txt](llms.txt) has a compact API reference
> you can give to the LLM to help it work with this package.

## Requirements

- PHP 8.3+

## Installation

```bash
composer require rasuvaeff/yii3-feature-flags
```

## Usage

### Basic flag check

```php
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;

if ($featureFlags->isEnabled(
    flag: 'new-checkout',
    context: FlagContext::forUser(userId: $userId),
)) {
    // New checkout flow.
}
```

### Configuration

In your application config (`config/params.php`):

```php
return [
    'rasuvaeff/yii3-feature-flags' => [
        'flags' => [
            'new-checkout' => [
                'enabled' => true,
                'salt' => 'new-checkout-v1',
                'rollout' => 25,
                'killSwitch' => false,
                'environments' => ['production'],
            ],
        ],
    ],
];
```

### Configuration options

| Key | Type | Default | Description |
|---|---|---|---|
| `enabled` | `bool` | `true` | Master switch for the flag |
| `salt` | `string` | flag name | Hash salt for deterministic rollout |
| `rollout` | `int` | `100` | Percentage of subjects to include (0..100) |
| `killSwitch` | `bool` | `false` | Immediately disable the flag, overrides all rules |
| `environments` | `list<string>` | `[]` | Restrict to specific environments (empty = all) |

### FlagContext

```php
// By user ID
$context = FlagContext::forUser(userId: 'user-42');

// By tenant ID
$context = FlagContext::forTenant(tenantId: 'tenant-1');

// By environment
$context = FlagContext::forEnvironment(environment: 'production');

// Combined
$context = FlagContext::forUser(userId: 'user-42')
    ->withEnvironment(environment: 'production');
```

### Forced values

Use forced values for QA/debug overrides on existing flags:

```php
$context = FlagContext::forUser(userId: 'user-42')
    ->withForcedFlag(flag: 'new-checkout', enabled: true);

$featureFlags->isEnabled(flag: 'new-checkout', context: $context);
```

A forced value never re-enables a flag that has its kill switch active — the
kill switch wins.

### Strict mode

Unknown flags return `false` by default. Enable strict mode to throw instead:

```php
$featureFlags = new FeatureFlags(
    provider: $provider,
    strictMode: true,
);
```

### Evaluation result

Get detailed information about why a flag is enabled or disabled:

```php
$result = $featureFlags->evaluate(
    flag: 'new-checkout',
    context: FlagContext::forUser(userId: $userId),
);

$result->isEnabled();      // bool
$result->getReason();      // EvaluationReason enum
$result->getFlagName();    // string
```

`EvaluationReason` carries a single machine-readable reason instead of a set of
overlapping booleans:

| Case | `enabled` | When |
|---|---|---|
| `Enabled` | `true` | Flag on, no targeting/rollout exclusion |
| `Disabled` | `false` | `enabled: false` on the flag |
| `KillSwitch` | `false` | `killSwitch: true` overrides everything |
| `RolloutExcluded` | `false` | Subject outside the rollout bucket |
| `EnvironmentExcluded` | `false` | Context environment not in the flag's allowlist |
| `Forced` | caller-set | `FlagContext::withForcedFlag()` overrode the result |
| `Unknown` | `false` | Flag not registered (non-strict mode) |
```

### Kill switch

Set `killSwitch: true` in config to immediately disable a flag, overriding all
targeting, rollout and forced-value rules.

### Percentage rollout

Deterministic assignment using `sha256(salt . ':' . subjectId)`:

- Same `salt` + `subjectId` always produces the same result.
- Changing `salt` resets assignment (intentional re-randomization).
- Changing weights shifts boundaries; some subjects may change variant.

## Public API

| Class | Description |
|---|---|
| `FeatureFlags` | Facade service: `isEnabled()`, `isDisabled()`, `evaluate()`, `has()` |
| `Flag` | Immutable flag value object |
| `FlagConfig` | Config DTO for programmatic flag definitions |
| `FlagContext` | Evaluation context (userId, tenantId, environment) |
| `FlagProvider` | Read-only interface for flag sources |
| `WritableFlagProvider` | `extends FlagProvider`: adds `save(Flag)`, `remove(string)` |
| `ConfigFlagProvider` | Provider from PHP config arrays (read-only) |
| `FlagRegistry` | Named flag lookup |
| `FlagEvaluator` | Core evaluation logic |
| `PercentageRollout` | Deterministic percentage assignment |
| `EvaluationResult` | Detailed evaluation outcome (private constructor; 7 static factories) |
| `EvaluationReason` | String-backed enum of evaluation outcomes |
| `MetricsRecorder` | Interface for recording evaluation metrics |
| `NullMetricsRecorder` | No-op default implementation |

## Storage backends

The core wires only the `FeatureFlags` facade. The `FlagProvider` implementation
is supplied by **exactly one** provider — a storage backend or, for config-array
flags, the application. This keeps backends drop-in: install one and it is
wired automatically, with no `Duplicate key` config conflict.

| Package | Description |
|---|---|
| [`rasuvaeff/yii3-feature-flags-db`](https://github.com/rasuvaeff/yii3-feature-flags-db) | Database (yiisoft/db) with PSR-16 caching and migration |

Install a backend and you are done — it binds `FlagProvider` for you:

```bash
composer require rasuvaeff/yii3-feature-flags-db
```

### Writable backends

A backend may implement `WritableFlagProvider` to support programmatic flag
CRUD (e.g. via an admin UI). `DbFlagProvider` and `CachedFlagProvider` in
`yii3-feature-flags-db` both implement it; `ConfigFlagProvider` does not — it is
read-only.

```php
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;

/** @var WritableFlagProvider $provider */
$provider->save(flag: new Flag(name: 'new-checkout', rollout: 25));
$provider->remove(name: 'old-checkout');
```

`save()` is an upsert keyed by the flag `name`. Implementations that decorate a
cache (e.g. `CachedFlagProvider`) invalidate themselves after a successful write.

## Metrics

`FeatureFlags` accepts an optional `MetricsRecorder` as its last constructor
argument. After each `evaluate()` call it receives the resulting
`EvaluationResult` exactly once (never on the throw path in strict mode). The
default is `NullMetricsRecorder`, which is a no-op — passing nothing is safe.

```php
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;

$recorder = new class implements MetricsRecorder {
    #[\Override]
    public function recordEvaluation(\Rasuvaeff\Yii3FeatureFlags\EvaluationResult $result): void
    {
        // ship $result->getReason()->value to your metrics backend
    }
};

$featureFlags = new FeatureFlags(provider: $provider, recorder: $recorder);
```

Adapter packages (`-psr-logger`, `-prometheus`, …) may be added later; the core
**does not** bind `MetricsRecorder` in its `config/di.php`, so installing an
adapter next to the core will never trigger a `Duplicate key` error.

### Config-only setup

Without a storage backend, define flags in `params.php` and bind `FlagProvider`
to `ConfigFlagProvider` once in your application config (`config/common/di/*.php`):

```php
use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;

/** @var array $params */

return [
    FlagProvider::class => [
        'class' => ConfigFlagProvider::class,
        '__construct()' => [
            'flags' => $params['rasuvaeff/yii3-feature-flags']['flags'],
        ],
    ],
];
```

Bind `FlagProvider` from a single source — installing two backends (or a backend
plus a manual config binding) reintroduces the `Duplicate key` conflict.

## Security

- Flag names validated by regex (`/^[a-z][a-z0-9._-]*$/`).
- Rollout percentage validated (0..100 range).
- No user data is logged or stored by the core package.
- Kill switch provides emergency shutoff capability.

## Examples

See [examples/](examples/) for runnable scripts.
Examples are expected to execute without fatal errors and stay aligned with the
documented public API.

## Development

```bash
make install     # composer install
make build       # full gate: validate + cs + psalm + test
make cs-fix      # fix code style
make psalm       # static analysis
make test        # run tests
make test-coverage  # run coverage
make mutation       # mutation testing
make release-check  # build + rector + bc-check + mutation
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
