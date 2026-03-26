<?php

namespace App\Enums;

enum TaskLabel: string {
    case FIX = 'FIX';
    case FEATURE = 'FEATURE';
    case TEST = 'TEST';
    case DOC = 'DOC';
    case REFACTOR = 'REFACTOR';
}