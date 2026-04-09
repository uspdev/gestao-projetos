<?php

namespace App\Enums\Project;

enum ProjectUserRole: string
{
    case OWNER = 'OWNER';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';
}