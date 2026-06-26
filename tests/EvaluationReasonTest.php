<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(EvaluationReason::class)]
final class EvaluationReasonTest
{
    public function hasSevenCases(): void
    {
        Assert::count(EvaluationReason::cases(), 7);
    }

    public static function caseValueProvider(): iterable
    {
        yield 'enabled' => [EvaluationReason::Enabled, 'enabled'];
        yield 'disabled' => [EvaluationReason::Disabled, 'disabled'];
        yield 'kill_switch' => [EvaluationReason::KillSwitch, 'kill_switch'];
        yield 'rollout_excluded' => [EvaluationReason::RolloutExcluded, 'rollout_excluded'];
        yield 'environment_excluded' => [EvaluationReason::EnvironmentExcluded, 'environment_excluded'];
        yield 'forced' => [EvaluationReason::Forced, 'forced'];
        yield 'unknown' => [EvaluationReason::Unknown, 'unknown'];
    }

    #[DataProvider('caseValueProvider')]
    public function stringValueMatchesExpected(EvaluationReason $reason, string $expected): void
    {
        Assert::same($reason->value, $expected);
    }

    #[DataProvider('caseValueProvider')]
    public function fromReturnsMatchingCase(EvaluationReason $reason, string $value): void
    {
        Assert::same(EvaluationReason::from($value), $reason);
    }

    #[DataProvider('caseValueProvider')]
    public function tryFromReturnsMatchingCase(EvaluationReason $reason, string $value): void
    {
        Assert::same(EvaluationReason::tryFrom($value), $reason);
    }

    public function tryFromReturnsNullForUnknownValue(): void
    {
        Assert::null(EvaluationReason::tryFrom('does-not-exist'));
    }

    public function fromThrowsForUnknownValue(): void
    {
        Expect::exception(\ValueError::class);

        EvaluationReason::from('does-not-exist');
    }
}
