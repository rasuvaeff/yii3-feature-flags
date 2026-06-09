<?php

declare(strict_types=1);

use Rasuvaeff\Yii3FeatureFlags\FeatureFlags;

/** @var array $params */

return [
    FeatureFlags::class => [
        '__construct()' => [
            'strictMode' => $params['rasuvaeff/yii3-feature-flags']['strictMode'],
        ],
    ],
];
