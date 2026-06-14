<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class EvaluationResult
{
    private function __construct(
        private string $flagName,
        private bool $enabled,
        private EvaluationReason $reason,
    ) {}

    public static function enabled(string $flagName): self
    {
        return new self(flagName: $flagName, enabled: true, reason: EvaluationReason::Enabled);
    }

    public static function disabled(string $flagName): self
    {
        return new self(flagName: $flagName, enabled: false, reason: EvaluationReason::Disabled);
    }

    public static function killSwitch(string $flagName): self
    {
        return new self(flagName: $flagName, enabled: false, reason: EvaluationReason::KillSwitch);
    }

    public static function rolloutExcluded(string $flagName): self
    {
        return new self(flagName: $flagName, enabled: false, reason: EvaluationReason::RolloutExcluded);
    }

    public static function environmentExcluded(string $flagName): self
    {
        return new self(flagName: $flagName, enabled: false, reason: EvaluationReason::EnvironmentExcluded);
    }

    public static function forced(string $flagName, bool $value): self
    {
        return new self(flagName: $flagName, enabled: $value, reason: EvaluationReason::Forced);
    }

    public static function unknown(string $flagName): self
    {
        return new self(flagName: $flagName, enabled: false, reason: EvaluationReason::Unknown);
    }

    public function getFlagName(): string
    {
        return $this->flagName;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getReason(): EvaluationReason
    {
        return $this->reason;
    }
}
