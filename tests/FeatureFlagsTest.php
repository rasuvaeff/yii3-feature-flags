<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Rasuvaeff\Yii3FeatureFlags\Exception\UnknownFlagException;
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Rasuvaeff\Yii3FeatureFlags\FlagEvaluator;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;

#[CoversClass(FeatureFlags::class)]
final class FeatureFlagsTest extends TestCase
{
    private FeatureFlags $featureFlags;

    #[\Override]
    protected function setUp(): void
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

    #[Test]
    public function isEnabledReturnsTrueForEnabledFlag(): void
    {
        $this->assertTrue(
            $this->featureFlags->isEnabled(flag: 'enabled-flag'),
        );
    }

    #[Test]
    public function isEnabledReturnsFalseForDisabledFlag(): void
    {
        $this->assertFalse(
            $this->featureFlags->isEnabled(flag: 'disabled-flag'),
        );
    }

    #[Test]
    public function isEnabledReturnsFalseForKillSwitched(): void
    {
        $this->assertFalse(
            $this->featureFlags->isEnabled(flag: 'kill-switched'),
        );
    }

    #[Test]
    public function isEnabledReturnsFalseForUnknownFlagInNonStrictMode(): void
    {
        $this->assertFalse(
            $this->featureFlags->isEnabled(flag: 'unknown'),
        );
    }

    #[Test]
    public function isEnabledThrowsForUnknownFlagInStrictMode(): void
    {
        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: []),
            strictMode: true,
        );

        $this->expectException(UnknownFlagException::class);

        $featureFlags->isEnabled(flag: 'unknown');
    }

    #[Test]
    public function isDisabledReturnsOppositeOfIsEnabled(): void
    {
        $this->assertTrue(
            $this->featureFlags->isDisabled(flag: 'disabled-flag'),
        );
        $this->assertFalse(
            $this->featureFlags->isDisabled(flag: 'enabled-flag'),
        );
    }

    #[Test]
    public function hasReturnsTrueForExistingFlag(): void
    {
        $this->assertTrue($this->featureFlags->has('enabled-flag'));
    }

    #[Test]
    public function hasReturnsFalseForUnknownFlag(): void
    {
        $this->assertFalse($this->featureFlags->has('unknown'));
    }

    #[Test]
    public function evaluateReturnsResultForKnownFlag(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'kill-switched');

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::KillSwitch, $result->getReason());
    }

    #[Test]
    public function evaluateReturnsUnknownReasonForUnknownFlagInNonStrictMode(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'unknown');

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::Unknown, $result->getReason());
        $this->assertSame('unknown', $result->getFlagName());
    }

    #[Test]
    public function evaluateThrowsForUnknownFlagInStrictMode(): void
    {
        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: []),
            strictMode: true,
        );

        $this->expectException(UnknownFlagException::class);

        $featureFlags->evaluate(flag: 'unknown');
    }

    #[Test]
    public function environmentTargetingWorksWithContext(): void
    {
        $context = FlagContext::forEnvironment(environment: 'production');

        $this->assertTrue(
            $this->featureFlags->isEnabled(flag: 'env-flag', context: $context),
        );
    }

    #[Test]
    public function environmentTargetingExcludesMismatch(): void
    {
        $context = FlagContext::forEnvironment(environment: 'staging');

        $result = $this->featureFlags->evaluate(flag: 'env-flag', context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::EnvironmentExcluded, $result->getReason());
    }

    #[Test]
    public function rolloutWorksWithUserContext(): void
    {
        $result = $this->featureFlags->evaluate(
            flag: 'rollout-flag',
            context: FlagContext::forUser(userId: 'user-42'),
        );

        $this->assertSame('rollout-flag', $result->getFlagName());
    }

    #[Test]
    public function forcedValueOverridesRegularEvaluationForExistingFlag(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'disabled-flag', enabled: true);

        $result = $this->featureFlags->evaluate(flag: 'disabled-flag', context: $context);

        $this->assertTrue($result->isEnabled());
        $this->assertSame(EvaluationReason::Forced, $result->getReason());
    }

    #[Test]
    public function forcedValueDoesNotOverrideKillSwitch(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'kill-switched', enabled: true);

        $result = $this->featureFlags->evaluate(flag: 'kill-switched', context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::KillSwitch, $result->getReason());
    }

    #[Test]
    public function forcedValueIsIgnoredForUnknownFlag(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'unknown', enabled: true);

        $this->assertFalse(
            $this->featureFlags->isEnabled(flag: 'unknown', context: $context),
        );
    }

    #[Test]
    public function customEvaluatorIsUsedWhenProvided(): void
    {
        $customEvaluator = new FlagEvaluator();

        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'test' => ['enabled' => true, 'rollout' => 100],
            ]),
            evaluator: $customEvaluator,
        );

        $this->assertTrue($featureFlags->isEnabled(flag: 'test'));
    }

    #[Test]
    public function evaluateWithoutContextUsesEmptyContext(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'enabled-flag');

        $this->assertTrue($result->isEnabled());
    }

    #[Test]
    public function evaluateWithExplicitNullContextUsesEmptyContext(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'enabled-flag');

        $this->assertTrue($result->isEnabled());
    }

    #[Test]
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

        $this->assertSame(2, $spy->calls);
        $this->assertNotNull($spy->last);
        $this->assertSame('flag', $spy->last->getFlagName());
    }

    #[Test]
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

        $this->assertSame(1, $spy->calls);
        $this->assertSame(EvaluationReason::Unknown, $spy->last->getReason());
        $this->assertSame($result, $spy->last);
    }

    #[Test]
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

        $this->assertSame(1, $spy->calls);
        $this->assertSame(EvaluationReason::Forced, $spy->last->getReason());
        $this->assertSame($result, $spy->last);
    }

    #[Test]
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

        $this->assertSame(0, $spy->calls);
    }

    #[Test]
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

        $this->assertSame($result->getFlagName(), $evaluated->getFlagName());
        $this->assertSame($result->isEnabled(), $evaluated->isEnabled());
        $this->assertSame($result->getReason(), $evaluated->getReason());
    }

    #[Test]
    public function defaultsToNullMetricsRecorderWhenNoneProvided(): void
    {
        $featureFlags = new FeatureFlags(
            provider: new ConfigFlagProvider(flags: [
                'flag' => ['enabled' => true, 'rollout' => 100],
            ]),
        );

        $this->assertTrue($featureFlags->isEnabled(flag: 'flag'));
    }
}
