<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags\Tests;

use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\Flag;
use Rasuvaeff\Yii3FeatureFlags\FlagConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ConfigFlagProvider::class)]
final class ConfigFlagProviderTest
{
    public function returnsEmptyFlagsWhenNoConfig(): void
    {
        $provider = new ConfigFlagProvider(flags: []);

        Assert::same($provider->getFlags(), []);
    }

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

        Assert::count($flags, 1);
        Assert::array($flags)->hasKeys('my-flag');
        Assert::instanceOf($flags['my-flag'], Flag::class);
        Assert::same($flags['my-flag']->rollout, 50);
    }

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

        Assert::false($flag->enabled);
        Assert::same($flag->salt, 'my-flag-v2');
        Assert::same($flag->rollout, 25);
        Assert::true($flag->killSwitch);
        Assert::same($flag->environments, ['production']);
    }

    public function continuesProcessingAfterFlagConfigObject(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'first' => new FlagConfig(enabled: true),
            'second' => ['enabled' => false],
        ]);

        $flags = $provider->getFlags();

        Assert::count($flags, 2);
        Assert::array($flags)->hasKeys('first');
        Assert::array($flags)->hasKeys('second');
    }

    public function appliesDefaultsForMissingConfigKeys(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => [],
        ]);

        $flags = $provider->getFlags();
        $flag = $flags['my-flag'];

        Assert::true($flag->enabled);
        Assert::same($flag->salt, 'my-flag');
        Assert::same($flag->rollout, 100);
        Assert::false($flag->killSwitch);
        Assert::same($flag->environments, []);
    }

    public function createsMultipleFlags(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'flag-a' => ['enabled' => true],
            'flag-b' => ['enabled' => false],
        ]);

        $flags = $provider->getFlags();

        Assert::count($flags, 2);
        Assert::true($flags['flag-a']->enabled);
        Assert::false($flags['flag-b']->enabled);
    }

    public function emptySaltFallsBackToName(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => ['salt' => ''],
        ]);

        $flag = $provider->getFlags()['my-flag'];

        Assert::same($flag->salt, 'my-flag');
    }

    public function explicitSaltOverridesName(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'my-flag' => ['salt' => 'custom-salt'],
        ]);

        $flag = $provider->getFlags()['my-flag'];

        Assert::same($flag->salt, 'custom-salt');
    }

    public function missingKillSwitchDefaultsToFalse(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'flag-with-kill' => ['killSwitch' => true],
            'flag-without-kill' => [],
        ]);

        $flags = $provider->getFlags();

        Assert::true($flags['flag-with-kill']->killSwitch);
        Assert::false($flags['flag-without-kill']->killSwitch);
    }

    public function missingEnvironmentsDefaultsToEmpty(): void
    {
        $provider = new ConfigFlagProvider(flags: [
            'with-envs' => ['environments' => ['production']],
            'without-envs' => [],
        ]);

        $flags = $provider->getFlags();

        Assert::same($flags['with-envs']->environments, ['production']);
        Assert::same($flags['without-envs']->environments, []);
    }
}
