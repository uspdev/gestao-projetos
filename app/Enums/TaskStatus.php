<?php

namespace App\Enums;

enum TaskStatus: string {
    case TO_DO = 'TO_DO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case IN_REVIEW = 'IN_REVIEW';
    case DONE = 'DONE';
    case HOLD = 'HOLD';
}