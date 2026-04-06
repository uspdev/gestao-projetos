<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

enum ProjectUserRole: string
{
    case OWNER = 'OWNER';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';
}

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $project_id
 * @property int $user_id
 * @property \App\Models\ProjectUserRole $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectUser whereUserId($value)
 * @mixin \Eloquent
 */
class ProjectUser extends Pivot
{
    protected $casts = [
        'role' => ProjectUserRole::class,
    ];
}