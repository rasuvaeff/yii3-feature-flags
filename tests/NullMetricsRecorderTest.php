<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;
use Rasuvaeff\Yii3FeatureFlags\NullMetricsRecorder;

#[CoversClass(NullMetricsRecorder::class)]
final class NullMetricsRecorderTest extends TestCase
{
    #[Test]
    public function implementsMetricsRecorder(): void
    {
        $recorder = new NullMetricsRecorder();

        $this->assertInstanceOf(MetricsRecorder::class, $recorder);
    }

    #[Test]
    public function recordEvaluationDoesNotThrow(): void
    {
        $recorder = new NullMetricsRecorder();

        $this->expectNotToPerformAssertions();

        $recorder->recordEvaluation(
            result: EvaluationResult::forced(flagName: 'flag', value: false),
        );
    }

    #[Test]
    public function recordEvaluationAcceptsAnyReason(): void
    {
        $recorder = new NullMetricsRecorder();

        $this->expectNotToPerformAssertions();

        foreach (EvaluationReason::cases() as $reason) {
            $result = EvaluationResult::disabled(flagName: 'flag');
            $recorder->recordEvaluation(result: $result);
        }
    }
}
