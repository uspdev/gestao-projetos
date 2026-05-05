<?php

namespace App\Models;

use App\Enums\Project\ProjectUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectUser extends Pivot
{
    protected $casts = [
        'role' => ProjectUserRole::class,
    ];
}
