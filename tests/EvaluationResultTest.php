<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;

#[CoversClass(EvaluationResult::class)]
final class EvaluationResultTest extends TestCase
{
    #[Test]
    public function createsEnabledResult(): void
    {
        $result = new EvaluationResult(flagName: 'my-flag', enabled: true);

        $this->assertSame('my-flag', $result->getFlagName());
        $this->assertTrue($result->isEnabled());
        $this->assertFalse($result->isKillSwitchActive());
        $this->assertFalse($result->isRolloutExcluded());
        $this->assertFalse($result->isEnvironmentExcluded());
    }

    #[Test]
    public function createsKillSwitchResult(): void
    {
        $result = new EvaluationResult(
            flagName: 'my-flag',
            enabled: false,
            killSwitchActive: true,
        );

        $this->assertFalse($result->isEnabled());
        $this->assertTrue($result->isKillSwitchActive());
    }

    #[Test]
    public function createsRolloutExcludedResult(): void
    {
        $result = new EvaluationResult(
            flagName: 'my-flag',
            enabled: false,
            rolloutExcluded: true,
        );

        $this->assertFalse($result->isEnabled());
        $this->assertTrue($result->isRolloutExcluded());
    }

    #[Test]
    public function createsEnvironmentExcludedResult(): void
    {
        $result = new EvaluationResult(
            flagName: 'my-flag',
            enabled: false,
            environmentExcluded: true,
        );

        $this->assertFalse($result->isEnabled());
        $this->assertTrue($result->isEnvironmentExcluded());
    }
}
