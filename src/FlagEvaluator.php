<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final class FlagEvaluator
{
    public function __construct(
        private readonly PercentageRollout $rollout = new PercentageRollout(),
    ) {}

    public function evaluate(Flag $flag, FlagContext $context): EvaluationResult
    {
        if ($flag->killSwitch) {
            return new EvaluationResult(
                flagName: $flag->name,
                enabled: false,
                killSwitchActive: true,
            );
        }

        if (!$flag->enabled) {
            return new EvaluationResult(
                flagName: $flag->name,
                enabled: false,
            );
        }

        if ($flag->environments !== [] && $context->getEnvironment() !== null) {
            if (!in_array(needle: $context->getEnvironment(), haystack: $flag->environments, strict: true)) {
                return new EvaluationResult(
                    flagName: $flag->name,
                    enabled: false,
                    environmentExcluded: true,
                );
            }
        }

        $subjectId = $context->getUserId() ?? $context->getTenantId();

        if ($subjectId === null) {
            return new EvaluationResult(
                flagName: $flag->name,
                enabled: true,
            );
        }

        $enabled = $this->rollout->isEnabled(
            salt: $flag->salt,
            subjectId: $subjectId,
            rolloutPercentage: $flag->rollout,
        );

        return new EvaluationResult(
            flagName: $flag->name,
            enabled: $enabled,
            rolloutExcluded: !$enabled,
        );
    }
}
