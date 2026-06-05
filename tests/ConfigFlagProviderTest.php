<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagConfig;

#[CoversClass(ConfigFlagProvider::class)]
final class ConfigFlagProviderTest extends TestCase
{
    #[Test]
    public function returnsEmptyFlagsWhenNoConfig(): void
    {
        $provider = new ConfigFlagProvider(flags: []);

        $this->assertSame([], $provider->getFlags());
    }

    #[Test]
    public function createsFlagsFromConfig(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => [
                'enabled' => true,
                'salt' => 'my-flag-v1',
                'rollout' => 50,
                'killSwitch' => false,
                'environments' => ['production'],
            ],
        ]);

        $flags = $provider->getFlags();

        $this->assertCount(1, $flags);
        $this->assertArrayHasKey('my-flag', $flags);
        $this->assertInstanceOf(Flag::class, $flags['my-flag']);
        $this->assertSame(50, $flags['my-flag']->rollout);
    }

    #[Test]
    public function createsFlagsFromFlagConfigObjects(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => new FlagConfig(
                enabled: false,
                salt: 'my-flag-v2',
                rollout: 25,
                killSwitch: true,
                environments: ['production'],
            ),
        ]);

        $flag = $provider->getFlags()['my-flag'];

        $this->assertFalse($flag->enabled);
        $this->assertSame('my-flag-v2', $flag->salt);
        $this->assertSame(25, $flag->rollout);
        $this->assertTrue($flag->killSwitch);
        $this->assertSame(['production'], $flag->environments);
    }

    #[Test]
    public function continuesProcessingAfterFlagConfigObject(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'first' => new FlagConfig(enabled: true),
            'second' => ['enabled' => false],
        ]);

        $flags = $provider->getFlags();

        $this->assertCount(2, $flags);
        $this->assertArrayHasKey('first', $flags);
        $this->assertArrayHasKey('second', $flags);
    }

    #[Test]
    public function appliesDefaultsForMissingConfigKeys(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => [],
        ]);

        $flags = $provider->getFlags();
        $flag = $flags['my-flag'];

        $this->assertTrue($flag->enabled);
        $this->assertSame('my-flag', $flag->salt);
        $this->assertSame(100, $flag->rollout);
        $this->assertFalse($flag->killSwitch);
        $this->assertSame([], $flag->environments);
    }

    #[Test]
    public function createsMultipleFlags(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'flag-a' => ['enabled' => true],
            'flag-b' => ['enabled' => false],
        ]);

        $flags = $provider->getFlags();

        $this->assertCount(2, $flags);
        $this->assertTrue($flags['flag-a']->enabled);
        $this->assertFalse($flags['flag-b']->enabled);
    }

    #[Test]
    public function emptySaltFallsBackToName(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => ['salt' => ''],
        ]);

        $flag = $provider->getFlags()['my-flag'];

        $this->assertSame('my-flag', $flag->salt);
    }

    #[Test]
    public function explicitSaltOverridesName(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => ['salt' => 'custom-salt'],
        ]);

        $flag = $provider->getFlags()['my-flag'];

        $this->assertSame('custom-salt', $flag->salt);
    }

    #[Test]
    public function missingKillSwitchDefaultsToFalse(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'flag-with-kill' => ['killSwitch' => true],
            'flag-without-kill' => [],
        ]);

        $flags = $provider->getFlags();

        $this->assertTrue($flags['flag-with-kill']->killSwitch);
        $this->assertFalse($flags['flag-without-kill']->killSwitch);
    }

    #[Test]
    public function missingEnvironmentsDefaultsToEmpty(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'with-envs' => ['environments' => ['production']],
            'without-envs' => [],
        ]);

        $flags = $provider->getFlags();

        $this->assertSame(['production'], $flags['with-envs']->environments);
        $this->assertSame([], $flags['without-envs']->environments);
    }
}
