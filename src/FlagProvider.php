<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
interface FlagProvider
{
    /**
     * @return array<string, Flag>
     */
    public function getFlags(): array;
}
