<?php

namespace App\Models;

use App\Enums\Project\ProjectUserRole;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Task;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \Spatie\Permission\Traits\HasRoles;
    use \Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'codpes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->using(ProjectUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->withTimestamps();
    }

    public function isViewerOfProject(Project $project): bool
    {
        return $this->projects()
            ->where('project_id', $project->id)
            ->wherePivotIn('role', [
                ProjectUserRole::OWNER->value,
                ProjectUserRole::MEMBER->value,
                ProjectUserRole::VIEWER->value,
            ])
            ->exists();
    }

    public function belongsToProject(Project $project): bool
    {
        return $this->projects()
            ->where('project_id', $project->id)
            ->exists();
    }

    public function isMemberOfProject(Project $project): bool
    {
        return $this->projects()
            ->where('project_id', $project->id)
            ->wherePivotIn('role', [ProjectUserRole::OWNER->value, ProjectUserRole::MEMBER->value])
            ->exists();
    }

    public function isOwnerOfProject(Project $project): bool
    {
        return $this->projects()
            ->where('project_id', $project->id)
            ->wherePivot('role', ProjectUserRole::OWNER->value)
            ->exists();
    }

    public function isTaskAssignee(Task $task): bool
    {
        return $this->tasks()->where('task_id', $task->id)->exists();
    }

    public function scopeSelectableToProject(Builder $query, int $projectId): Builder
    {
        return $query->whereDoesntHave('projects', function ($q) use ($projectId) {
            $q->where('projects.id', $projectId);
        })->orderBy('name');
    }

    public function scopeSelectableToTask(Builder $query, int $taskId, int $projectId): Builder
    {
        return $query->whereHas('projects', function ($q) use ($projectId) {
            $q->where('projects.id', $projectId);
            $q->whereIn('project_user.role', [
                ProjectUserRole::OWNER->value,
                ProjectUserRole::MEMBER->value,
            ]);
        })
            ->whereDoesntHave('tasks', function ($q) use ($taskId) {
                $q->where('tasks.id', $taskId);
            })
            ->orderBy('name');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->can('admin');
    }

    public function isTaskCreator(Task $task): bool
    {
        return $task->created_by === $this->id;
    }
}
