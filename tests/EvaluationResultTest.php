<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;

#[CoversClass(EvaluationResult::class)]
final class EvaluationResultTest extends TestCase
{
    #[Test]
    public function enabledFactory(): void
    {
        $result = EvaluationResult::enabled(flagName: 'my-flag');

        $this->assertSame('my-flag', $result->getFlagName());
        $this->assertTrue($result->isEnabled());
        $this->assertSame(EvaluationReason::Enabled, $result->getReason());
    }

    #[Test]
    public function disabledFactory(): void
    {
        $result = EvaluationResult::disabled(flagName: 'my-flag');

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::Disabled, $result->getReason());
    }

    #[Test]
    public function killSwitchFactory(): void
    {
        $result = EvaluationResult::killSwitch(flagName: 'my-flag');

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::KillSwitch, $result->getReason());
    }

    #[Test]
    public function rolloutExcludedFactory(): void
    {
        $result = EvaluationResult::rolloutExcluded(flagName: 'my-flag');

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::RolloutExcluded, $result->getReason());
    }

    #[Test]
    public function environmentExcludedFactory(): void
    {
        $result = EvaluationResult::environmentExcluded(flagName: 'my-flag');

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::EnvironmentExcluded, $result->getReason());
    }

    #[Test]
    public function forcedFactoryWithTrue(): void
    {
        $result = EvaluationResult::forced(flagName: 'my-flag', value: true);

        $this->assertTrue($result->isEnabled());
        $this->assertSame(EvaluationReason::Forced, $result->getReason());
    }

    #[Test]
    public function forcedFactoryWithFalse(): void
    {
        $result = EvaluationResult::forced(flagName: 'my-flag', value: false);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::Forced, $result->getReason());
    }

    #[Test]
    public function unknownFactory(): void
    {
        $result = EvaluationResult::unknown(flagName: 'missing');

        $this->assertSame('missing', $result->getFlagName());
        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::Unknown, $result->getReason());
    }
}
