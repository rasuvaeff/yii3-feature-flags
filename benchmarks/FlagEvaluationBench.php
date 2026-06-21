<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Benchmarks;

use Rasuvaeff\Yii3FeatureFlags\EvaluationResult;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Rasuvaeff\Yii3FeatureFlags\FlagEvaluator;
use Testo\Bench;

/**
 * Compares FlagEvaluator::evaluate() at 100% rollout (fast path, no hash)
 * vs 50% rollout (must compute sha256 to determine the bucket).
 */
final class FlagEvaluationBench
{
    #[Bench(
        callables: [
            'partial_rollout' => [self::class, 'evaluatePartialRollout'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function evaluateFullRollout(): EvaluationResult
    {
        static $evaluator = null;
        static $flag = null;
        static $context = null;
        $evaluator ??= new FlagEvaluator();
        $flag ??= new Flag(name: 'feature.payments', rollout: 100);
        $context ??= FlagContext::forUser(userId: 'user-42');

        return $evaluator->evaluate(flag: $flag, context: $context);
    }

    public static function evaluatePartialRollout(): EvaluationResult
    {
        static $evaluator = null;
        static $flag = null;
        static $context = null;
        $evaluator ??= new FlagEvaluator();
        $flag ??= new Flag(name: 'feature.payments', rollout: 50);
        $context ??= FlagContext::forUser(userId: 'user-42');

        return $evaluator->evaluate(flag: $flag, context: $context);
    }
}
