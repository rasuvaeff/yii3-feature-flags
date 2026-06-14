<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class FlagEvaluator
{
    public function __construct(
        private PercentageRollout $rollout = new PercentageRollout(),
    ) {}

    public function evaluate(Flag $flag, FlagContext $context): EvaluationResult
    {
        if ($flag->killSwitch) {
            return EvaluationResult::killSwitch(flagName: $flag->name);
        }

        if (!$flag->enabled) {
            return EvaluationResult::disabled(flagName: $flag->name);
        }

        if ($flag->environments !== [] && $context->getEnvironment() !== null && !in_array(needle: $context->getEnvironment(), haystack: $flag->environments, strict: true)) {
            return EvaluationResult::environmentExcluded(flagName: $flag->name);
        }

        $subjectId = $context->getUserId() ?? $context->getTenantId();

        if ($subjectId === null) {
            return EvaluationResult::enabled(flagName: $flag->name);
        }

        $enabled = $this->rollout->isEnabled(
            salt: $flag->salt,
            subjectId: $subjectId,
            rolloutPercentage: $flag->rollout,
        );

        return $enabled
            ? EvaluationResult::enabled(flagName: $flag->name)
            : EvaluationResult::rolloutExcluded(flagName: $flag->name);
    }
}
