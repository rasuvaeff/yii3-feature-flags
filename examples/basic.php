<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;

$provider = new ConfigFlagProvider(flags: [
    'always-on' => [
        'enabled' => true,
        'rollout' => 100,
    ],
    'always-off' => [
        'enabled' => false,
    ],
    'kill-switched' => [
        'enabled' => true,
        'rollout' => 100,
        'killSwitch' => true,
    ],
    'rollout-50' => [
        'enabled' => true,
        'salt' => 'rollout-50-v1',
        'rollout' => 50,
    ],
]);

$ff = new FeatureFlags(provider: $provider);

echo "always-on: " . ($ff->isEnabled(flag: 'always-on') ? 'enabled' : 'disabled') . "\n";
echo "always-off: " . ($ff->isEnabled(flag: 'always-off') ? 'enabled' : 'disabled') . "\n";
echo "kill-switched: " . ($ff->isEnabled(flag: 'kill-switched') ? 'enabled' : 'disabled') . "\n";

echo "\nRollout 50% distribution (100 users):\n";
$enabled = 0;
for ($i = 1; $i <= 100; $i++) {
    if ($ff->isEnabled(flag: 'rollout-50', context: FlagContext::forUser(userId: (string) $i))) {
        $enabled++;
    }
}
echo "  Enabled: {$enabled}/100\n";

echo "\nDetailed evaluation for user-42:\n";
$result = $ff->evaluate(flag: 'rollout-50', context: FlagContext::forUser(userId: 'user-42'));
echo "  Enabled: " . ($result->isEnabled() ? 'yes' : 'no') . "\n";
echo "  Rollout excluded: " . ($result->isRolloutExcluded() ? 'yes' : 'no') . "\n";
