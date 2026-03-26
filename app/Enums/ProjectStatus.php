<?php

namespace App\Enums;

enum ProjectStatus: string {
    case PLANNING = 'PLANNING';
    case DEVELOPMENT = 'DEVELOPMENT';
    case PRODUCTION = 'PRODUCTION';
    case DEACTIVATED = 'DEACTIVATED';
    case MIGRATED = 'MIGRATED';
    case HOLD = 'HOLD';
}