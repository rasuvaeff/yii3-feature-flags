<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class NullMetricsRecorder implements MetricsRecorder
{
    #[\Override]
    public function recordEvaluation(EvaluationResult $result): void {}
}
