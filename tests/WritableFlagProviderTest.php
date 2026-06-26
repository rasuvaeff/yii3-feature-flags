<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

#[Test]
#[CoversNothing]
final class WritableFlagProviderTest
{
    public function extendsFlagProvider(): void
    {
        $reflection = new \ReflectionClass(WritableFlagProvider::class);

        Assert::true($reflection->isSubclassOf(FlagProvider::class));
    }

    public function hasSaveAndRemoveMethods(): void
    {
        $reflection = new \ReflectionClass(WritableFlagProvider::class);

        Assert::true($reflection->hasMethod('save'));
        Assert::true($reflection->hasMethod('remove'));
        Assert::true($reflection->hasMethod('getFlags'));
    }

    public function saveReturnsVoidAndAcceptsFlag(): void
    {
        $reflection = new \ReflectionMethod(WritableFlagProvider::class, 'save');

        Assert::same((string) $reflection->getReturnType(), 'void');
        $params = $reflection->getParameters();
        Assert::count($params, 1);
        Assert::same($params[0]->getName(), 'flag');
        Assert::same((string) $params[0]->getType(), Flag::class);
    }

    public function removeReturnsVoidAndAcceptsString(): void
    {
        $reflection = new \ReflectionMethod(WritableFlagProvider::class, 'remove');

        Assert::same((string) $reflection->getReturnType(), 'void');
        $params = $reflection->getParameters();
        Assert::count($params, 1);
        Assert::same($params[0]->getName(), 'name');
        Assert::same((string) $params[0]->getType(), 'string');
    }

    public function smokeRoundTripWithAnonymousImplementation(): void
    {
        $provider = new class implements WritableFlagProvider {
            /** @var array<string, Flag> */
            private array $flags = [];

            #[\Override]
            public function getFlags(): array
            {
                return $this->flags;
            }

            #[\Override]
            public function save(Flag $flag): void
            {
                $this->flags[$flag->name] = $flag;
            }

            #[\Override]
            public function remove(string $name): void
            {
                unset($this->flags[$name]);
            }
        };

        $flag = new Flag(name: 'new-flag', enabled: true);

        $provider->save(flag: $flag);

        Assert::same($provider->getFlags(), ['new-flag' => $flag]);

        $provider->remove(name: 'new-flag');

        Assert::same($provider->getFlags(), []);
    }
}
