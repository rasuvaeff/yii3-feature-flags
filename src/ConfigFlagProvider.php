<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final class ConfigFlagProvider implements FlagProvider
{
    /**
     * @param array<string, array{enabled?: bool, salt?: string, rollout?: int, killSwitch?: bool, environments?: list<string>}|FlagConfig> $flags
     */
    public function __construct(
        private readonly array $flags = [],
    ) {}

    #[\Override]
    public function getFlags(): array
    {
        $result = [];

        foreach ($this->flags as $name => $config) {
            if ($config instanceof FlagConfig) {
                $result[$name] = $config->toFlag(name: $name);
                continue;
            }

            $result[$name] = new Flag(
                name: $name,
                enabled: $config['enabled'] ?? true,
                salt: $config['salt'] ?? '',
                rollout: $config['rollout'] ?? 100,
                killSwitch: $config['killSwitch'] ?? false,
                environments: $config['environments'] ?? [],
            );
        }

        return $result;
    }
}
