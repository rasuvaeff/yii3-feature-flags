<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FlagContext::class)]
final class FlagContextTest
{
    public function createsUserContext(): void
    {
        $context = FlagContext::forUser(userId: 'user-42');

        Assert::same($context->getUserId(), 'user-42');
        Assert::null($context->getTenantId());
        Assert::null($context->getEnvironment());
        Assert::same($context->getForcedValues(), []);
    }

    public function createsTenantContext(): void
    {
        $context = FlagContext::forTenant(tenantId: 'tenant-1');

        Assert::same($context->getTenantId(), 'tenant-1');
        Assert::null($context->getUserId());
    }

    public function createsEnvironmentContext(): void
    {
        $context = FlagContext::forEnvironment(environment: 'production');

        Assert::same($context->getEnvironment(), 'production');
        Assert::null($context->getUserId());
    }

    public function createsEmptyContext(): void
    {
        $context = FlagContext::empty();

        Assert::null($context->getUserId());
        Assert::null($context->getTenantId());
        Assert::null($context->getEnvironment());
        Assert::same($context->getForcedValues(), []);
    }

    public function withUserIdReturnsNewInstance(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withUserId(userId: 'user-2');

        Assert::same($original->getUserId(), 'user-1');
        Assert::same($modified->getUserId(), 'user-2');
    }

    public function withTenantIdPreservesOtherValues(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withTenantId(tenantId: 'tenant-1');

        Assert::same($modified->getTenantId(), 'tenant-1');
        Assert::same($modified->getUserId(), 'user-1');
    }

    public function withEnvironmentPreservesOtherValues(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withEnvironment(environment: 'staging');

        Assert::same($modified->getEnvironment(), 'staging');
        Assert::same($modified->getUserId(), 'user-1');
    }

    public function withForcedFlagReturnsNewInstance(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withForcedFlag(flag: 'new-checkout', enabled: true);

        Assert::null($original->getForcedValue('new-checkout'));
        Assert::true($modified->getForcedValue('new-checkout'));
    }

    public function withoutForcedFlagRemovesOverride(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'new-checkout', enabled: false);
        $modified = $context->withoutForcedFlag(flag: 'new-checkout');

        Assert::false($context->getForcedValue('new-checkout'));
        Assert::null($modified->getForcedValue('new-checkout'));
    }
}
