<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(EvaluationResult::class)]
final class EvaluationResultTest
{
    public function enabledFactory(): void
    {
        $result = EvaluationResult::enabled(flagName: 'my-flag');

        Assert::same($result->getFlagName(), 'my-flag');
        Assert::true($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Enabled);
    }

    public function disabledFactory(): void
    {
        $result = EvaluationResult::disabled(flagName: 'my-flag');

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Disabled);
    }

    public function killSwitchFactory(): void
    {
        $result = EvaluationResult::killSwitch(flagName: 'my-flag');

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::KillSwitch);
    }

    public function rolloutExcludedFactory(): void
    {
        $result = EvaluationResult::rolloutExcluded(flagName: 'my-flag');

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::RolloutExcluded);
    }

    public function environmentExcludedFactory(): void
    {
        $result = EvaluationResult::environmentExcluded(flagName: 'my-flag');

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::EnvironmentExcluded);
    }

    public function forcedFactoryWithTrue(): void
    {
        $result = EvaluationResult::forced(flagName: 'my-flag', value: true);

        Assert::true($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Forced);
    }

    public function forcedFactoryWithFalse(): void
    {
        $result = EvaluationResult::forced(flagName: 'my-flag', value: false);

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Forced);
    }

    public function unknownFactory(): void
    {
        $result = EvaluationResult::unknown(flagName: 'missing');

        Assert::same($result->getFlagName(), 'missing');
        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Unknown);
    }
}
