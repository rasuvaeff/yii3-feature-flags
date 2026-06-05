<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\Exception\UnknownFlagException;
use Rasuvaeff\Yii3FeatureFlags\FlagRegistry;

#[CoversClass(FlagRegistry::class)]
final class FlagRegistryTest extends TestCase
{
    private FlagRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'flag-a' => ['enabled' => true],
            'flag-b' => ['enabled' => false],
        ]);

        $this->registry = new FlagRegistry(provider: $provider);
    }

    #[Test]
    public function hasReturnsTrueForExistingFlag(): void
    {
        $this->assertTrue($this->registry->has('flag-a'));
    }

    #[Test]
    public function hasReturnsFalseForMissingFlag(): void
    {
        $this->assertFalse($this->registry->has('unknown'));
    }

    #[Test]
    public function getReturnsFlagByName(): void
    {
        $flag = $this->registry->get('flag-a');

        $this->assertSame('flag-a', $flag->name);
    }

    #[Test]
    public function getThrowsForUnknownFlag(): void
    {
        $this->expectException(UnknownFlagException::class);

        $this->registry->get('unknown');
    }

    #[Test]
    public function allReturnsAllFlags(): void
    {
        $all = $this->registry->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('flag-a', $all);
        $this->assertArrayHasKey('flag-b', $all);
    }
}
