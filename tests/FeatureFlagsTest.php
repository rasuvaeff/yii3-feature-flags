<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\Exception\UnknownFlagException;
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Rasuvaeff\Yii3FeatureFlags\FlagEvaluator;

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
        $this->assertTrue($result->isKillSwitchActive());
    }

    #[Test]
    public function evaluateReturnsFalseForUnknownFlagInNonStrictMode(): void
    {
        $result = $this->featureFlags->evaluate(flag: 'unknown');

        $this->assertFalse($result->isEnabled());
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

        $this->assertFalse(
            $this->featureFlags->isEnabled(flag: 'env-flag', context: $context),
        );
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

        $this->assertTrue(
            $this->featureFlags->isEnabled(flag: 'disabled-flag', context: $context),
        );
    }

    #[Test]
    public function forcedValueDoesNotOverrideKillSwitch(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'kill-switched', enabled: true);

        $result = $this->featureFlags->evaluate(flag: 'kill-switched', context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertTrue($result->isKillSwitchActive());
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
        $result = $this->featureFlags->evaluate(flag: 'enabled-flag', context: null);

        $this->assertTrue($result->isEnabled());
    }
}
