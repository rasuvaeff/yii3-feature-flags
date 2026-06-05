<?php

declare(strict_types=1);

/**
 * Yii3 config-plugin integration example.
 *
 * In a real Yii3 application, config/params.php and config/di.php are merged
 * automatically by yiisoft/config. This script shows the wiring manually.
 */

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagContext;

// Application config (typically in config/params.php)
$appParams = [
    'rasuvaeff/yii3-feature-flags' => [
        'flags' => [
            'new-checkout' => [
                'enabled' => true,
                'salt' => 'new-checkout-v1',
                'rollout' => 25,
                'killSwitch' => false,
                'environments' => ['production'],
            ],
        ],
    ],
];

// DI wiring (typically in config/di.php)
$provider = new ConfigFlagProvider(
    flags: $appParams['rasuvaeff/yii3-feature-flags']['flags'],
);
$featureFlags = new FeatureFlags(provider: $provider);

// Usage in application code
$userId = 'user-42';
$context = FlagContext::forUser(userId: $userId)
    ->withEnvironment(environment: 'production');

if ($featureFlags->isEnabled(flag: 'new-checkout', context: $context)) {
    echo "User {$userId} gets new checkout\n";
} else {
    echo "User {$userId} gets old checkout\n";
}
