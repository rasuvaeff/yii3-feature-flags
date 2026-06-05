<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class FlagConfig
{
    /**
     * @param list<string> $environments
     */
    public function __construct(
        public bool $enabled = true,
        public string $salt = '',
        public int $rollout = 100,
        public bool $killSwitch = false,
        public array $environments = [],
    ) {}

    public function toFlag(string $name): Flag
    {
        return new Flag(
            name: $name,
            enabled: $this->enabled,
            salt: $this->salt,
            rollout: $this->rollout,
            killSwitch: $this->killSwitch,
            environments: $this->environments,
        );
    }
}
