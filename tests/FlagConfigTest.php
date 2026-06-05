<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagConfig;

#[CoversClass(FlagConfig::class)]
final class FlagConfigTest extends TestCase
{
    #[Test]
    public function constructorAppliesDefaults(): void
    {
        $config = new FlagConfig();

        $this->assertTrue($config->enabled);
        $this->assertSame('', $config->salt);
        $this->assertSame(100, $config->rollout);
        $this->assertFalse($config->killSwitch);
        $this->assertSame([], $config->environments);
    }

    #[Test]
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

        $this->assertInstanceOf(Flag::class, $flag);
        $this->assertSame('new-checkout', $flag->name);
        $this->assertFalse($flag->enabled);
        $this->assertSame('checkout-v1', $flag->salt);
        $this->assertSame(25, $flag->rollout);
        $this->assertTrue($flag->killSwitch);
        $this->assertSame(['production'], $flag->environments);
    }
}
