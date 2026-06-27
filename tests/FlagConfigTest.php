<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(FlagConfig::class)]
final class FlagConfigTest
{
    public function constructorAppliesDefaults(): void
    {
        $config = new FlagConfig();

        Assert::true($config->enabled);
        Assert::same($config->salt, '');
        Assert::same($config->rollout, 100);
        Assert::false($config->killSwitch);
        Assert::same($config->environments, []);
    }

    public function convertsToFlag(): void
    {
        $config = new FlagConfig(
            enabled: false,
            salt: 'checkout-v1',
            rollout: 25,
            killSwitch: true,
            environments: ['production'],
        );

        $flag = $config->toFlag(name: 'new-checkout');

        Assert::instanceOf($flag, Flag::class);
        Assert::same($flag->name, 'new-checkout');
        Assert::false($flag->enabled);
        Assert::same($flag->salt, 'checkout-v1');
        Assert::same($flag->rollout, 25);
        Assert::true($flag->killSwitch);
        Assert::same($flag->environments, ['production']);
    }
}
