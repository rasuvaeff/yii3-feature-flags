<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class Flag
{
    private const string NAME_PATTERN = '/^[a-z][a-z0-9._-]*$/';

    public string $name;

    public bool $enabled;

    public string $salt;

    public int $rollout;

    public bool $killSwitch;

    /**
     * @var list<string>
     */
    public array $environments;

    /**
     * @param list<string> $environments
     */
    public function __construct(
        string $name,
        bool $enabled = true,
        string $salt = '',
        int $rollout = 100,
        bool $killSwitch = false,
        array $environments = [],
    ) {
        $this->validateName($name);
        $this->validateRollout($rollout);

        $this->name = $name;
        $this->enabled = $enabled;
        $this->salt = $salt !== '' ? $salt : $name;
        $this->rollout = $rollout;
        $this->killSwitch = $killSwitch;
        $this->environments = $environments;
    }

    private function validateName(string $name): void
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new Exception\InvalidFlagNameException(
                message: sprintf('Invalid flag name "%s"', $name),
            );
        }
    }

    private function validateRollout(int $rollout): void
    {
        if ($rollout < 0 || $rollout > 100) {
            throw new Exception\InvalidFlagNameException(
                message: sprintf('Rollout percentage must be 0..100, got %d', $rollout),
            );
        }
    }
}
