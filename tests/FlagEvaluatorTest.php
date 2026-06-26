<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;
use Rasuvaeff\Yii3FeatureFlags\FlagEvaluator;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(FlagEvaluator::class)]
final class FlagEvaluatorTest
{
    private FlagEvaluator $evaluator;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->evaluator = new FlagEvaluator();
    }

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

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::KillSwitch);
    }

    public function disabledFlagReturnsDisabledReason(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: false);
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Disabled);
    }

    public function enabledFlagWithNoContextReturnsTrue(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100);
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::true($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Enabled);
    }

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

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::EnvironmentExcluded);
    }

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

        Assert::true($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Enabled);
    }

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

        Assert::true($result->isEnabled());
        Assert::notSame($result->getReason(), EvaluationReason::EnvironmentExcluded);
    }

    public function noEnvironmentRestrictionPasses(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100, environments: []);
        $context = FlagContext::forEnvironment(environment: 'staging');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::true($result->isEnabled());
    }

    public function rolloutWithUserContext(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 0);
        $context = FlagContext::forUser(userId: 'user-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::RolloutExcluded);
    }

    public function rolloutWithTenantContext(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100);
        $context = FlagContext::forTenant(tenantId: 'tenant-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::true($result->isEnabled());
    }

    public function userContextTakesPriorityOverTenant(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 0);
        $context = FlagContext::forUser(userId: 'user-1')->withTenantId(tenantId: 'tenant-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::false($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::RolloutExcluded);
    }

    public function tenantOnlyContextUsesTenantForRollout(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 100);
        $context = FlagContext::forTenant(tenantId: 'tenant-1');

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::true($result->isEnabled());
    }

    public function contextWithBothUsesUserIdNotTenantId(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, salt: 'priority-test', rollout: 50);
        $contextWithBoth = FlagContext::forUser(userId: 'user-1')->withTenantId(tenantId: 'tenant-1');
        $contextWithUserOnly = FlagContext::forUser(userId: 'user-1');
        $contextWithTenantOnly = FlagContext::forTenant(tenantId: 'tenant-1');

        $resultBoth = $this->evaluator->evaluate(flag: $flag, context: $contextWithBoth);
        $resultUserOnly = $this->evaluator->evaluate(flag: $flag, context: $contextWithUserOnly);
        $resultTenantOnly = $this->evaluator->evaluate(flag: $flag, context: $contextWithTenantOnly);

        Assert::same($resultBoth->isEnabled(), $resultUserOnly->isEnabled());
        Assert::notSame($resultUserOnly->isEnabled(), $resultTenantOnly->isEnabled());
    }

    public function enabledReasonWhenSubjectIdIsNull(): void
    {
        $flag = new Flag(name: 'my-flag', enabled: true, rollout: 0);
        $context = FlagContext::empty();

        $result = $this->evaluator->evaluate(flag: $flag, context: $context);

        Assert::true($result->isEnabled());
        Assert::same($result->getReason(), EvaluationReason::Enabled);
    }

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

        Assert::same($result->getReason(), EvaluationReason::Enabled);
    }
}
