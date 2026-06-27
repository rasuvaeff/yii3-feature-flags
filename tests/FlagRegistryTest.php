<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\Exception\UnknownFlagException;
use Rasuvaeff\Yii3FeatureFlags\FlagRegistry;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(FlagRegistry::class)]
final class FlagRegistryTest
{
    private FlagRegistry $registry;

    #[BeforeTest]
    public function setUp(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'flag-a' => ['enabled' => true],
            'flag-b' => ['enabled' => false],
        ]);

        $this->registry = new FlagRegistry(provider: $provider);
    }

    public function hasReturnsTrueForExistingFlag(): void
    {
        Assert::true($this->registry->has('flag-a'));
    }

    public function hasReturnsFalseForMissingFlag(): void
    {
        Assert::false($this->registry->has('unknown'));
    }

    public function getReturnsFlagByName(): void
    {
        $flag = $this->registry->get('flag-a');

        Assert::same($flag->name, 'flag-a');
    }

    public function getThrowsForUnknownFlag(): void
    {
        Expect::exception(UnknownFlagException::class);

        $this->registry->get('unknown');
    }

    public function allReturnsAllFlags(): void
    {
        $all = $this->registry->all();

        Assert::count($all, 2);
        Assert::array($all)->hasKeys('flag-a');
        Assert::array($all)->hasKeys('flag-b');
    }
}
