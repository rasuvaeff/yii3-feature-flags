<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class EvaluationResult
{
    public function __construct(
        private string $flagName,
        private bool $enabled,
        private bool $killSwitchActive = false,
        private bool $rolloutExcluded = false,
        private bool $environmentExcluded = false,
    ) {}

    public function getFlagName(): string
    {
        return $this->flagName;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isKillSwitchActive(): bool
    {
        return $this->killSwitchActive;
    }

    public function isRolloutExcluded(): bool
    {
        return $this->rolloutExcluded;
    }

    public function isEnvironmentExcluded(): bool
    {
        return $this->environmentExcluded;
    }
}
