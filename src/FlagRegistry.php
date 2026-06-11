<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class FlagRegistry
{
    /**
     * @var array<string, Flag>
     */
    private array $flags;

    public function __construct(
        FlagProvider $provider,
    ) {
        $this->flags = $provider->getFlags();
    }

    public function has(string $name): bool
    {
        return isset($this->flags[$name]);
    }

    public function get(string $name): Flag
    {
        if (!isset($this->flags[$name])) {
            throw new Exception\UnknownFlagException(
                message: sprintf('Unknown flag "%s"', $name),
            );
        }

        return $this->flags[$name];
    }

    /**
     * @return array<string, Flag>
     */
    public function all(): array
    {
        return $this->flags;
    }
}
