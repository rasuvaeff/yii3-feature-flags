<?php

declare(strict_types=1);

use Rasuvaeff\Yii3FeatureFlags\ConfigFlagProvider;
use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;
use Rasuvaeff\Yii3FeatureFlags\FlagProvider;

/** @var array $params */

return [
    FlagProvider::class => [
        'class' => ConfigFlagProvider::class,
        '__construct()' => [
            'flags' => $params['rasuvaeff/yii3-feature-flags']['flags'],
        ],
    ],
    FeatureFlags::class => [
        '__construct()' => [
            'strictMode' => $params['rasuvaeff/yii3-feature-flags']['strictMode'],
        ],
    ],
];
