<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\EvaluationReason;

#[CoversClass(EvaluationReason::class)]
final class EvaluationReasonTest extends TestCase
{
    #[Test]
    public function hasSevenCases(): void
    {
        $this->assertCount(7, EvaluationReason::cases());
    }

    /**
     * @return iterable<string, array{EvaluationReason, string}>
     */
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
    #[Test]
    public function stringValueMatchesExpected(EvaluationReason $reason, string $expected): void
    {
        $this->assertSame($expected, $reason->value);
    }

    #[DataProvider('caseValueProvider')]
    #[Test]
    public function fromReturnsMatchingCase(EvaluationReason $reason, string $value): void
    {
        $this->assertSame($reason, EvaluationReason::from($value));
    }

    #[DataProvider('caseValueProvider')]
    #[Test]
    public function tryFromReturnsMatchingCase(EvaluationReason $reason, string $value): void
    {
        $this->assertSame($reason, EvaluationReason::tryFrom($value));
    }

    #[Test]
    public function tryFromReturnsNullForUnknownValue(): void
    {
        $this->assertNull(EvaluationReason::tryFrom('does-not-exist'));
    }

    #[Test]
    public function fromThrowsForUnknownValue(): void
    {
        $this->expectException(\ValueError::class);

        EvaluationReason::from('does-not-exist');
    }
}
