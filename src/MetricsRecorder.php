<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
interface MetricsRecorder
{
    public function recordEvaluation(EvaluationResult $result): void;
}
