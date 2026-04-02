<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

enum ProjectUserRole: string
{
    case OWNER = 'OWNER';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';
}

class ProjectUser extends Pivot
{
    protected $casts = [
        'role' => ProjectUserRole::class,
    ];
}