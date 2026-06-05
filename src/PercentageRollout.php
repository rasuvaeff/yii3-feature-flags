<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
final readonly class PercentageRollout
{
    public function isEnabled(
        string $salt,
        string $subjectId,
        int $rolloutPercentage,
    ): bool {
        if ($rolloutPercentage === 0) {
            return false;
        }

        if ($rolloutPercentage === 100) {
            return true;
        }

        $digest = hash(algo: 'sha256', data: $salt . ':' . $subjectId);
        $bucket = hexdec(hex_string: substr(string: $digest, offset: 0, length: 8)) % 100;

        return $bucket < $rolloutPercentage;
    }
}
