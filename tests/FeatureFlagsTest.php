<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Rasuvaeff\Yii3FeatureFlags\Exception\UnknownFlagException;
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Rasuvaeff\Yii3FeatureFlags\FlagEvaluator;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(FeatureFlags::class)]
final class FeatureFlagsTest
{
    private FeatureFlags $featureFlags;

    #[BeforeTest]
    public function setUp(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'enabled-flag' => [
                'enabled' => true,
                'rollout' => 100,
            ],
            'disabled-flag' => [
                'enabled' => false,
            ],
            'kill-switched' => [
                'enabled' => true,
                'rollout' => 100,
                'killSwitch' => true,
            ],
            'rollout-flag' => [
                'enabled' => true,
                'salt' => 'rollout-v1',
                'rollout' => 50,
            ],
            'env-flag' => [
                'enabled' => true,
                'rollout' => 100,
                'environments' => ['production'],
            ],
        ]);

        $this->featureFlags = new FeatureFlags(provider: $provider);
    }

    public function isEnabledReturnsTrueForEnabledFlag(): void
    {
        Assert::true(
            $this->featureFlags->isEnabled(flag: 'enabled-flag'),
        );
    }

    public function isEnabledReturnsFalseForDisabledFlag(): void
    {
        Assert::false(
            $this->featureFlags->isEnabled(flag: 'disabled-flag'),
        );
    }

    public function isEnabledReturnsFalseForKillSwitched(): void
    {
        Assert::false(
            $this->featureFlags->isEnabled(flag: 'kill-switched'),
        );
    }

    public function isEnabledReturnsFalseForUnknownFlagInNonStrictMode(): void
    {
        Assert::false(
            $this->featureFlags->isEnabled(flag: 'unknown'),
        );
    }

    public function isEnabledThrowsForUnknownFlagInStrictMode(): void
    {
        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: []),
            strictMode: true,
        );

        Expect::exception(UnknownFlagException::class);

        $featureFlags->isEnabled(flag: 'unknown');
    }

    public function isDisabledReturnsOppositeOfIsEnabled(): void
    {
        Assert::true(
            $this->featureFlags->isDisabled(flag: 'disabled-flag'),
        );
        Assert::false(
            $this->featureFlags->isDisabled(flag: 'enabled-flag'),
        );
    }

    public function hasReturnsTrueForExistingFlag(): void
    {
        Assert::true($this->featureFlags->has('enabled-flag'));
    }

    public function hasReturnsFalseForUnknownFlag(): void
    {
        Assert::false($this->featureFlags->has('unknown'));
    }

    public function evaluateReturnsResultForKnownFlag(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'kill-switched');

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::KillSwitch);
    }

    public function evaluateReturnsUnknownReasonForUnknownFlagInNonStrictMode(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'unknown');

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Unknown);
        Assert::same($result->getFlagName(), 'unknown');
    }

    public function evaluateThrowsForUnknownFlagInStrictMode(): void
    {
        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: []),
            strictMode: true,
        );

        Expect::exception(UnknownFlagException::class);

        $featureFlags->evaluate(flag: 'unknown');
    }

    public function environmentTargetingWorksWithContext(): void
    {
        $context = FlagContext::forEnvironment(environment: 'production');

        Assert::true(
            $this->featureFlags->isEnabled(flag: 'env-flag', context: $context),
        );
    }

    public function environmentTargetingExcludesMismatch(): void
    {
        $context = FlagContext::forEnvironment(environment: 'staging');

        $result = $this->featureFlags->evaluate(flag: 'env-flag', context: $context);

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::EnvironmentExcluded);
    }

    public function rolloutWorksWithUserContext(): void
    {
        $result = $this->featureFlags->evaluate(
            flag: 'rollout-flag',
            context: FlagContext::forUser(userId: 'user-42'),
        );

        Assert::same($result->getFlagName(), 'rollout-flag');
    }

    public function forcedValueOverridesRegularEvaluationForExistingFlag(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'disabled-flag', enabled: true);

        $result = $this->featureFlags->evaluate(flag: 'disabled-flag', context: $context);

        Assert::true($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Forced);
    }

    public function forcedValueDoesNotOverrideKillSwitch(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'kill-switched', enabled: true);

        $result = $this->featureFlags->evaluate(flag: 'kill-switched', context: $context);

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::KillSwitch);
    }

    public function forcedValueIsIgnoredForUnknownFlag(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'unknown', enabled: true);

        Assert::false(
            $this->featureFlags->isEnabled(flag: 'unknown', context: $context),
        );
    }

    public function customEvaluatorIsUsedWhenProvided(): void
    {
        $customEvaluator = new FlagEvaluator();

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'test' => ['enabled' => true, 'rollout' => 100],
            ]),
            evaluator: $customEvaluator,
        );

        Assert::true($featureFlags->isEnabled(flag: 'test'));
    }

    public function evaluateWithoutContextUsesEmptyContext(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'enabled-flag');

        Assert::true($result->isEnabled());
    }

    public function evaluateWithExplicitNullContextUsesEmptyContext(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'enabled-flag');

        Assert::true($result->isEnabled());
    }

    public function recorderIsCalledExactlyOncePerEvaluate(): void
    {
        $spy = new class implements MetricsRecorder {
            public int $calls = 0;

            public ?EvaluationResult $last = null;

            #[\Override]
            public function recordEvaluation(EvaluationResult $result): void
            {
                $this->calls++;
                $this->last = $result;
            }
        };

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'flag' => ['enabled' => true, 'rollout' => 100],
            ]),
            recorder: $spy,
        );

        $featureFlags->evaluate(flag: 'flag');
        $featureFlags->isEnabled(flag: 'flag');

        Assert::same($spy->calls, 2);
        Assert::notNull($spy->last);
        Assert::same($spy->last->getFlagName(), 'flag');
    }

    public function recorderIsCalledForUnknownFlagPath(): void
    {
        $spy = new class implements MetricsRecorder {
            public int $calls = 0;

            public ?EvaluationResult $last = null;

            #[\Override]
            public function recordEvaluation(EvaluationResult $result): void
            {
                $this->calls++;
                $this->last = $result;
            }
        };

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: []),
            recorder: $spy,
        );

        $result = $featureFlags->evaluate(flag: 'missing');

        Assert::same($spy->calls, 1);
        Assert::same($spy->last->getReason(), EvaluationReason::Unknown);
        Assert::same($result, $spy->last);
    }

    public function recorderIsCalledForForcedValuePath(): void
    {
        $spy = new class implements MetricsRecorder {
            public int $calls = 0;

            public ?EvaluationResult $last = null;

            #[\Override]
            public function recordEvaluation(EvaluationResult $result): void
            {
                $this->calls++;
                $this->last = $result;
            }
        };

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'flag' => ['enabled' => false, 'rollout' => 100],
            ]),
            recorder: $spy,
        );

        $context = FlagContext::empty()->withForcedFlag(flag: 'flag', enabled: true);
        $result = $featureFlags->evaluate(flag: 'flag', context: $context);

        Assert::same($spy->calls, 1);
        Assert::same($spy->last->getReason(), EvaluationReason::Forced);
        Assert::same($result, $spy->last);
    }

    public function recorderIsNotCalledWhenStrictModeThrows(): void
    {
        $spy = new class implements MetricsRecorder {
            public int $calls = 0;

            #[\Override]
            public function recordEvaluation(EvaluationResult $result): void
            {
                $this->calls++;
            }
        };

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: []),
            strictMode: true,
            recorder: $spy,
        );

        try {
            $featureFlags->evaluate(flag: 'unknown');
        } catch (UnknownFlagException) {
        }

        Assert::same($spy->calls, 0);
    }

    public function recorderDoesNotChangeResult(): void
    {
        $result = EvaluationResult::enabled(flagName: 'flag');

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'flag' => ['enabled' => true, 'rollout' => 100],
            ]),
            recorder: new class implements MetricsRecorder {
                #[\Override]
                public function recordEvaluation(EvaluationResult $result): void
                {
                    // Intentionally no-op: recorder must not influence the result.
                }
            },
        );

        $evaluated = $featureFlags->evaluate(flag: 'flag');

        Assert::same($evaluated->getFlagName(), $result->getFlagName());
        Assert::same($evaluated->isEnabled(), $result->isEnabled());
        Assert::same($evaluated->getReason(), $result->getReason());
    }

    public function defaultsToNullMetricsRecorderWhenNoneProvided(): void
    {
        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'flag' => ['enabled' => true, 'rollout' => 100],
            ]),
        );

        Assert::true($featureFlags->isEnabled(flag: 'flag'));
    }
}
