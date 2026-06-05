<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;

#[CoversClass(FlagContext::class)]
final class FlagContextTest extends TestCase
{
    #[Test]
    public function createsUserContext(): void
    {
        $context = FlagContext::forUser(userId: 'user-42');

        $this->assertSame('user-42', $context->getUserId());
        $this->assertNull($context->getTenantId());
        $this->assertNull($context->getEnvironment());
        $this->assertSame([], $context->getForcedValues());
    }

    #[Test]
    public function createsTenantContext(): void
    {
        $context = FlagContext::forTenant(tenantId: 'tenant-1');

        $this->assertSame('tenant-1', $context->getTenantId());
        $this->assertNull($context->getUserId());
    }

    #[Test]
    public function createsEnvironmentContext(): void
    {
        $context = FlagContext::forEnvironment(environment: 'production');

        $this->assertSame('production', $context->getEnvironment());
        $this->assertNull($context->getUserId());
    }

    #[Test]
    public function createsEmptyContext(): void
    {
        $context = FlagContext::empty();

        $this->assertNull($context->getUserId());
        $this->assertNull($context->getTenantId());
        $this->assertNull($context->getEnvironment());
        $this->assertSame([], $context->getForcedValues());
    }

    #[Test]
    public function withUserIdReturnsNewInstance(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withUserId(userId: 'user-2');

        $this->assertSame('user-1', $original->getUserId());
        $this->assertSame('user-2', $modified->getUserId());
    }

    #[Test]
    public function withTenantIdPreservesOtherValues(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withTenantId(tenantId: 'tenant-1');

        $this->assertSame('tenant-1', $modified->getTenantId());
        $this->assertSame('user-1', $modified->getUserId());
    }

    #[Test]
    public function withEnvironmentPreservesOtherValues(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withEnvironment(environment: 'staging');

        $this->assertSame('staging', $modified->getEnvironment());
        $this->assertSame('user-1', $modified->getUserId());
    }

    #[Test]
    public function withForcedFlagReturnsNewInstance(): void
    {
        $original = FlagContext::forUser(userId: 'user-1');
        $modified = $original->withForcedFlag(flag: 'new-checkout', enabled: true);

        $this->assertNull($original->getForcedValue('new-checkout'));
        $this->assertTrue($modified->getForcedValue('new-checkout'));
    }

    #[Test]
    public function withoutForcedFlagRemovesOverride(): void
    {
        $context = FlagContext::empty()->withForcedFlag(flag: 'new-checkout', enabled: false);
        $modified = $context->withoutForcedFlag(flag: 'new-checkout');

        $this->assertFalse($context->getForcedValue('new-checkout'));
        $this->assertNull($modified->getForcedValue('new-checkout'));
    }
}
