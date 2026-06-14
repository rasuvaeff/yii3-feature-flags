<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3FeatureFlags;

/**
 * @api
 */
enum EvaluationReason: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case KillSwitch = 'kill_switch';
    case RolloutExcluded = 'rollout_excluded';
    case EnvironmentExcluded = 'environment_excluded';
    case Forced = 'forced';
    case Unknown = 'unknown';
}
