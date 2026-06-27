<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\Exception\InvalidFlagNameException;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(Flag::class)]
final class FlagTest
{
    public function createsWithDefaults(): void
    {
        $flag = new Flag(name: 'my-flag');

        Assert::same($flag->name, 'my-flag');
        Assert::true($flag->enabled);
        Assert::same($flag->salt, 'my-flag');
        Assert::same($flag->rollout, 100);
        Assert::false($flag->killSwitch);
        Assert::same($flag->environments, []);
    }

    public function createsWithCustomSalt(): void
    {
        $flag = new Flag(name: 'my-flag', salt: 'custom-salt');

        Assert::same($flag->salt, 'custom-salt');
    }

    public function usesNameAsSaltWhenEmpty(): void
    {
        $flag = new Flag(name: 'my-flag', salt: '');

        Assert::same($flag->salt, 'my-flag');
    }

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

        Assert::same($flag->name, 'new-checkout');
        Assert::false($flag->enabled);
        Assert::same($flag->salt, 'checkout-v1');
        Assert::same($flag->rollout, 25);
        Assert::true($flag->killSwitch);
        Assert::same($flag->environments, ['production', 'staging']);
    }

    public function throwsOnInvalidName(): void
    {
        Expect::exception(InvalidFlagNameException::class);

        new Flag(name: 'INVALID');
    }

    public function throwsOnInvalidRolloutTooHigh(): void
    {
        Expect::exception(\InvalidArgumentException::class);

        new Flag(name: 'my-flag', rollout: 101);
    }

    public function throwsOnInvalidRolloutNegative(): void
    {
        Expect::exception(\InvalidArgumentException::class);

        new Flag(name: 'my-flag', rollout: -1);
    }

    public function rolloutErrorIsPlainInvalidArgumentExceptionNotInvalidFlagNameException(): void
    {
        try {
            new Flag(name: 'my-flag', rollout: 101);
            Assert::fail('Expected exception was not thrown');
        } catch (InvalidFlagNameException) {
            Assert::fail('Rollout validation must not throw InvalidFlagNameException');
        } catch (\InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Rollout percentage must be 0..100');
        }
    }

    public function acceptsBoundaryRolloutValues(): void
    {
        $zero = new Flag(name: 'my-flag', rollout: 0);
        $hundred = new Flag(name: 'my-flag', rollout: 100);

        Assert::same($zero->rollout, 0);
        Assert::same($hundred->rollout, 100);
    }

    public static function validFlagNameProvider(): iterable
    {
        yield 'simple' => ['my-flag'];
        yield 'with dots' => ['my.flag'];
        yield 'with underscores' => ['my_flag'];
        yield 'with numbers' => ['flag123'];
        yield 'single char' => ['a'];
        yield 'complex' => ['new.checkout-v2_test'];
    }

    #[DataProvider('validFlagNameProvider')]
    public function acceptsValidFlagNames(string $name): void
    {
        $flag = new Flag(name: $name);

        Assert::same($flag->name, $name);
    }

    public static function invalidFlagNameProvider(): iterable
    {
        yield 'uppercase' => ['MyFlag'];
        yield 'starts with number' => ['1flag'];
        yield 'starts with dash' => ['-flag'];
        yield 'starts with dot' => ['.flag'];
        yield 'spaces' => ['my flag'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidFlagNameProvider')]
    public function rejectsInvalidFlagNames(string $name): void
    {
        Expect::exception(InvalidFlagNameException::class);

        new Flag(name: $name);
    }
}
