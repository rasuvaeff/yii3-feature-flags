<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Rasuvaeff\Yii3FeatureFlags\MetricsRecorder;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

#[Test]
#[CoversNothing]
final class MetricsRecorderTest
{
    public function anonymousImplementationReceivesResult(): void
    {
        $spy = new class implements MetricsRecorder {
            public ?EvaluationResult $received = null;

            #[\Override]
            public function recordEvaluation(EvaluationResult $result): void
            {
                $this->received = $result;
            }
        };

        $result = EvaluationResult::enabled(flagName: 'flag');

        $spy->recordEvaluation(result: $result);

        Assert::same($result, $spy->received);
    }
}
