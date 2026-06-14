<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
interface WritableFlagProvider extends FlagProvider
{
    public function save(Flag $flag): void;

    public function remove(string $name): void;
}
