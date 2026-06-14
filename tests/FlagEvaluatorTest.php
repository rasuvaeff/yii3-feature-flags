<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Rasuvaeff\Yii3FeatureFlags\FlagEvaluator;

#[CoversClass(FlagEvaluator::class)]
final class FlagEvaluatorTest extends TestCase
{
    private FlagEvaluator $evaluator;

    #[\Override]
    protected function setUp(): void
    {
        $this->evaluator = new FlagEvaluator();
    }

    #[Test]
    public function killSwitchOverridesEverything(): void
    {
        $flag = new Flag(
            name: 'my-flag',
            enabled: true,
            rollout: 100,
            killSwitch: true,
        );
        $context = FlagContext::forUser(userId: 'user-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::KillSwitch, $result->getReason());
    }

    #[Test]
    public function disabledFlagReturnsDisabledReason(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: false);
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::Disabled, $result->getReason());
    }

    #[Test]
    public function enabledFlagWithNoContextReturnsTrue(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100);
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
        $this->assertSame(EvaluationReason::Enabled, $result->getReason());
    }

    #[Test]
    public function environmentMismatchExcludes(): void
    {
        $flag = new Flag(
            name: 'my-flag',
            enabled: true,
            rollout: 100,
            environments: ['production'],
        );
        $context = FlagContext::forEnvironment(environment: 'staging');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::EnvironmentExcluded, $result->getReason());
    }

    #[Test]
    public function environmentMatchIncludes(): void
    {
        $flag = new Flag(
            name: 'my-flag',
            enabled: true,
            rollout: 100,
            environments: ['production', 'staging'],
        );
        $context = FlagContext::forEnvironment(environment: 'production');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
        $this->assertSame(EvaluationReason::Enabled, $result->getReason());
    }

    #[Test]
    public function environmentRestrictionDoesNotApplyWithoutContextEnvironment(): void
    {
        $flag = new Flag(
            name: 'my-flag',
            enabled: true,
            rollout: 100,
            environments: ['production'],
        );
        $context = FlagContext::forUser(userId: 'user-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
        $this->assertNotSame(EvaluationReason::EnvironmentExcluded, $result->getReason());
    }

    #[Test]
    public function noEnvironmentRestrictionPasses(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100, environments: []);
        $context = FlagContext::forEnvironment(environment: 'staging');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
    }

    #[Test]
    public function rolloutWithUserContext(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 0);
        $context = FlagContext::forUser(userId: 'user-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::RolloutExcluded, $result->getReason());
    }

    #[Test]
    public function rolloutWithTenantContext(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100);
        $context = FlagContext::forTenant(tenantId: 'tenant-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
    }

    #[Test]
    public function userContextTakesPriorityOverTenant(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 0);
        $context = FlagContext::forUser(userId: 'user-1')->withTenantId(tenantId: 'tenant-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertFalse($result->isEnabled());
        $this->assertSame(EvaluationReason::RolloutExcluded, $result->getReason());
    }

    #[Test]
    public function tenantOnlyContextUsesTenantForRollout(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100);
        $context = FlagContext::forTenant(tenantId: 'tenant-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
    }

    #[Test]
    public function contextWithBothUsesUserIdNotTenantId(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, salt: 'priority-test', rollout: 50);
        $contextWithBoth = FlagContext::forUser(userId: 'user-1')->withTenantId(tenantId: 'tenant-1');
        $contextWithUserOnly = FlagContext::forUser(userId: 'user-1');
        $contextWithTenantOnly = FlagContext::forTenant(tenantId: 'tenant-1');

        $resultBoth = $this->evaluator->evaluate(flag: $flag, context: $contextWithBoth);
        $resultUserOnly = $this->evaluator->evaluate(flag: $flag, context: $contextWithUserOnly);
        $resultTenantOnly = $this->evaluator->evaluate(flag: $flag, context: $contextWithTenantOnly);

        $this->assertSame($resultBoth->isEnabled(), $resultUserOnly->isEnabled());
        $this->assertNotSame($resultUserOnly->isEnabled(), $resultTenantOnly->isEnabled());
    }

    #[Test]
    public function enabledReasonWhenSubjectIdIsNull(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 0);
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertTrue($result->isEnabled());
        $this->assertSame(EvaluationReason::Enabled, $result->getReason());
    }

    #[Test]
    public function enabledReasonWithEnvironmentButNoContextEnvironment(): void
    {
        $flag = new Flag(
            name: 'my-flag',
            enabled: true,
            rollout: 100,
            environments: ['production'],
        );
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        $this->assertSame(EvaluationReason::Enabled, $result->getReason());
    }
}
