<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;
use Rasuvaeff\Yii3FeatureFlags\WritableFlagProvider;

#[CoversNothing]
final class WritableFlagProviderTest extends TestCase
{
    #[Test]
    public function extendsFlagProvider(): void
    {
        $reflection = new \ReflectionClass(WritableFlagProvider::class);

        $this->assertTrue($reflection->isSubclassOf(FlagProvider::class));
    }

    #[Test]
    public function hasSaveAndRemoveMethods(): void
    {
        $reflection = new \ReflectionClass(WritableFlagProvider::class);

        $this->assertTrue($reflection->hasMethod('save'));
        $this->assertTrue($reflection->hasMethod('remove'));
        $this->assertTrue($reflection->hasMethod('getFlags'));
    }

    #[Test]
    public function saveReturnsVoidAndAcceptsFlag(): void
    {
        $reflection = new \ReflectionMethod(WritableFlagProvider::class, 'save');

        $this->assertSame('void', (string) $reflection->getReturnType());
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('flag', $params[0]->getName());
        $this->assertSame(Flag::class, (string) $params[0]->getType());
    }

    #[Test]
    public function removeReturnsVoidAndAcceptsString(): void
    {
        $reflection = new \ReflectionMethod(WritableFlagProvider::class, 'remove');

        $this->assertSame('void', (string) $reflection->getReturnType());
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('string', (string) $params[0]->getType());
    }

    #[Test]
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

        $this->assertSame(['new-flag' => $flag], $provider->getFlags());

        $provider->remove(name: 'new-flag');

        $this->assertSame([], $provider->getFlags());
    }
}
