<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\Exception\InvalidFlagNameException;
use Rasuvaeff\Yii3FeatureFlags\Flag;

#[CoversClass(Flag::class)]
final class FlagTest extends TestCase
{
    #[Test]
    public function createsWithDefaults(): void
    {
        $flag = new Flag(name: 'my-flag');

        $this->assertSame('my-flag', $flag->name);
        $this->assertTrue($flag->enabled);
        $this->assertSame('my-flag', $flag->salt);
        $this->assertSame(100, $flag->rollout);
        $this->assertFalse($flag->killSwitch);
        $this->assertSame([], $flag->environments);
    }

    #[Test]
    public function createsWithCustomSalt(): void
    {
        $flag = new Flag(name: 'my-flag', salt: 'custom-salt');

        $this->assertSame('custom-salt', $flag->salt);
    }

    #[Test]
    public function usesNameAsSaltWhenEmpty(): void
    {
        $flag = new Flag(name: 'my-flag', salt: '');

        $this->assertSame('my-flag', $flag->salt);
    }

    #[Test]
    public function createsWithAllParameters(): void
    {
        $flag = new Flag(
            name: 'new-checkout',
            enabled: false,
            salt: 'checkout-v1',
            rollout: 25,
            killSwitch: true,
            environments: ['production', 'staging'],
        );

        $this->assertSame('new-checkout', $flag->name);
        $this->assertFalse($flag->enabled);
        $this->assertSame('checkout-v1', $flag->salt);
        $this->assertSame(25, $flag->rollout);
        $this->assertTrue($flag->killSwitch);
        $this->assertSame(['production', 'staging'], $flag->environments);
    }

    #[Test]
    public function throwsOnInvalidName(): void
    {
        $this->expectException(InvalidFlagNameException::class);

        new Flag(name: 'INVALID');
    }

    #[Test]
    public function throwsOnInvalidRolloutTooHigh(): void
    {
        $this->expectException(InvalidFlagNameException::class);

        new Flag(name: 'my-flag', rollout: 101);
    }

    #[Test]
    public function throwsOnInvalidRolloutNegative(): void
    {
        $this->expectException(InvalidFlagNameException::class);

        new Flag(name: 'my-flag', rollout: -1);
    }

    #[Test]
    public function acceptsBoundaryRolloutValues(): void
    {
        $zero = new Flag(name: 'my-flag', rollout: 0);
        $hundred = new Flag(name: 'my-flag', rollout: 100);

        $this->assertSame(0, $zero->rollout);
        $this->assertSame(100, $hundred->rollout);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validFlagNameProvider(): array
    {
        return [
            'simple' => ['my-flag'],
            'with dots' => ['my.flag'],
            'with underscores' => ['my_flag'],
            'with numbers' => ['flag123'],
            'single char' => ['a'],
            'complex' => ['new.checkout-v2_test'],
        ];
    }

    #[DataProvider('validFlagNameProvider')]
    #[Test]
    public function acceptsValidFlagNames(string $name): void
    {
        $flag = new Flag(name: $name);

        $this->assertSame($name, $flag->name);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFlagNameProvider(): array
    {
        return [
            'uppercase' => ['MyFlag'],
            'starts with number' => ['1flag'],
            'starts with dash' => ['-flag'],
            'starts with dot' => ['.flag'],
            'spaces' => ['my flag'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidFlagNameProvider')]
    #[Test]
    public function rejectsInvalidFlagNames(string $name): void
    {
        $this->expectException(InvalidFlagNameException::class);

        new Flag(name: $name);
    }
}
