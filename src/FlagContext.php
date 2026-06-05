<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class FlagContext
{
    /**
     * @param array<string, bool> $forcedValues
     */
    public function __construct(
        private ?string $userId = null,
        private ?string $tenantId = null,
        private ?string $environment = null,
        private array $forcedValues = [],
    ) {}

    public static function forUser(string $userId): self
    {
        return new self(userId: $userId);
    }

    public static function forTenant(string $tenantId): self
    {
        return new self(tenantId: $tenantId);
    }

    public static function forEnvironment(string $environment): self
    {
        return new self(environment: $environment);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function getForcedValue(string $flag): ?bool
    {
        return $this->forcedValues[$flag] ?? null;
    }

    /**
     * @return array<string, bool>
     */
    public function getForcedValues(): array
    {
        return $this->forcedValues;
    }

    public function withUserId(string $userId): self
    {
        return new self(
            userId: $userId,
            tenantId: $this->tenantId,
            environment: $this->environment,
            forcedValues: $this->forcedValues,
        );
    }

    public function withTenantId(string $tenantId): self
    {
        return new self(
            userId: $this->userId,
            tenantId: $tenantId,
            environment: $this->environment,
            forcedValues: $this->forcedValues,
        );
    }

    public function withEnvironment(string $environment): self
    {
        return new self(
            userId: $this->userId,
            tenantId: $this->tenantId,
            environment: $environment,
            forcedValues: $this->forcedValues,
        );
    }

    public function withForcedFlag(string $flag, bool $enabled): self
    {
        $forcedValues = $this->forcedValues;
        $forcedValues[$flag] = $enabled;

        return new self(
            userId: $this->userId,
            tenantId: $this->tenantId,
            environment: $this->environment,
            forcedValues: $forcedValues,
        );
    }

    public function withoutForcedFlag(string $flag): self
    {
        $forcedValues = $this->forcedValues;
        unset($forcedValues[$flag]);

        return new self(
            userId: $this->userId,
            tenantId: $this->tenantId,
            environment: $this->environment,
            forcedValues: $forcedValues,
        );
    }
}
