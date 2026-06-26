<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;
use Rasuvaeff\Yii3FeatureFlags\NullMetricsRecorder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(NullMetricsRecorder::class)]
final class NullMetricsRecorderTest
{
    public function implementsMetricsRecorder(): void
    {
        $recorder = new NullMetricsRecorder();

        Assert::instanceOf($recorder, MetricsRecorder::class);
    }

    public function recordEvaluationDoesNotThrow(): void
    {
        $recorder = new NullMetricsRecorder();

        $recorder->recordEvaluation(
            result: EvaluationResult::forced(flagName: 'flag', value: false),
        );

        Assert::true(true);
    }

    public function recordEvaluationAcceptsAnyReason(): void
    {
        $recorder = new NullMetricsRecorder();

        foreach (EvaluationReason::cases() as $reason) {
            $result = EvaluationResult::disabled(flagName: 'flag');
            $recorder->recordEvaluation(result: $result);
        }

        Assert::true(true);
    }
}
