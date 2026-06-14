<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class FeatureFlags
{
    private FlagRegistry $registry;

    private FlagEvaluator $evaluator;

    private MetricsRecorder $recorder;

    public function __construct(
        FlagProvider $provider,
        ?FlagEvaluator $evaluator = null,
        private bool $strictMode = false,
        ?MetricsRecorder $recorder = null,
    ) {
        $this->registry = new FlagRegistry(provider: $provider);
        $this->evaluator = $evaluator ?? new FlagEvaluator();
        $this->recorder = $recorder ?? new NullMetricsRecorder();
    }

    public function isEnabled(
        string $flag,
        ?FlagContext $context = null,
    ): bool {
        return $this->evaluate(flag: $flag, context: $context)->isEnabled();
    }

    public function isDisabled(
        string $flag,
        ?FlagContext $context = null,
    ): bool {
        return !$this->isEnabled(flag: $flag, context: $context);
    }

    public function evaluate(
        string $flag,
        ?FlagContext $context = null,
    ): EvaluationResult {
        $context ??= FlagContext::empty();

        if (!$this->registry->has($flag)) {
            if ($this->strictMode) {
                throw new Exception\UnknownFlagException(
                    message: sprintf('Unknown flag "%s"', $flag),
                );
            }

            $result = EvaluationResult::unknown(flagName: $flag);

            $this->recorder->recordEvaluation(result: $result);

            return $result;
        }

        $resolved = $this->registry->get($flag);
        $forcedValue = $context->getForcedValue($flag);

        if ($forcedValue !== null && !$resolved->killSwitch) {
            $result = EvaluationResult::forced(flagName: $flag, value: $forcedValue);

            $this->recorder->recordEvaluation(result: $result);

            return $result;
        }

        $result = $this->evaluator->evaluate(
            flag: $resolved,
            context: $context,
        );

        $this->recorder->recordEvaluation(result: $result);

        return $result;
    }

    public function has(string $flag): bool
    {
        return $this->registry->has($flag);
    }
}
