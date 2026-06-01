<?php

namespace App\Models;

use App\Enums\Project\ProjectPermissionInheritance;
use App\Enums\Project\ProjectUserRole;
use App\Enums\Task\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \Spatie\Permission\Traits\HasRoles;
    use \Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Lista projetos pinados do usuário
     */
    public function pinnedProjects()
    {
        return $this->projects()->wherePivot('pinned', true)->get();
    }

    /**
     * Lista as tasks do usuário
     *
     * agrupadas por status se a visualização for kanban
     * ou em uma lista única ordenada caso contrário.
     */
    public function tasksByStatus($taskView = null, $tasksDone = 0)
    {
        $tasksByStatus = $this->tasks()
            ->with(['project', 'users'])
            ->withTasksModuleEnabled()
            ->when(! $tasksDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy(
                Project::select('name')
                    ->whereColumn('projects.id', 'tasks.project_id')
            )
            ->orderBy('priority', 'asc')
            ->latest()
            ->get();

        if ($taskView === 'kanban') {
            $tasksByStatus = $tasksByStatus->groupBy('status');
        }
        return $tasksByStatus;
    }

    /**
     * Relacionamento com projects
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->using(ProjectUser::class)
            ->withPivot('role', 'pinned')
            ->withTimestamps();
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->using(TaskUser::class)
            ->withTimestamps();
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
                ProjectUserRole::ADMIN->value,
                ProjectUserRole::CONTRIBUTOR->value,
            ]);
        })
            ->whereDoesntHave('tasks', function ($q) use ($taskId) {
                $q->where('tasks.id', $taskId);
            })
            ->orderBy('name');
    }

    public function isMemberOfProject(Project $project): bool
    {
        return $this->projects()
            ->where('project_id', $project->id)
            ->exists();
    }

    public function isViewerOfProject(Project $project): bool
    {
        // 1. Escopo mais próximo: verifica a role local primeiro.
        $localRole = $project->userRole($this);
        if ($localRole !== null) {
            return in_array($localRole, [
                ProjectUserRole::ADMIN,
                ProjectUserRole::CONTRIBUTOR,
                ProjectUserRole::VIEWER,
            ]);
        }

        // 2. Herança (Pai): Só chega aqui se o usuário NÃO for membro do projeto filho.

        // Se não é subprojeto, não há herança a considerar.
        if (!$project->isSubproject()) {
            return false;
        }
        // Se a herança for NONE, ignoramos a role do pai.
        if (in_array($project->permission_inheritance, [ProjectPermissionInheritance::NONE], true)) {
            return false;
        }

        // Verifica recursivamente no pai
        return $project->parent ? $this->isViewerOfProject($project->parent) : false;
    }

    public function isContributorOfProject(Project $project): bool
    {
        // Escopo mais próximo: apenas verifica a role local, sem considerar heranças.
        $localRole = $project->userRole($this);

        return in_array($localRole, [
            ProjectUserRole::ADMIN,
            ProjectUserRole::CONTRIBUTOR,
        ]);
    }

    public function isAdminOfProject(Project $project): bool
    {
        // Escopo mais próximo: Apenas verifica a role local, sem considerar heranças.
        return $project->userRole($this) === ProjectUserRole::ADMIN;
    }

    /**
     * Retorna a Role que o usuário tem direito de herdar do projeto pai.
     * Retorna null se ele já for membro do projeto atual ou não tiver herança aplicável.
     */
    public function getInheritedRoleFor(Project $project): ?ProjectUserRole
    {
        if ($this->isMemberOfProject($project)) {
            return null;
        }
        if (!$project->isSubproject() || $project->permission_inheritance !== ProjectPermissionInheritance::FULL) {
            return null;
        }
        $parent = $project->parent;
        if (!$parent) {
            return null;
        }

        // herança de apenas 1 nível, pegamos a role explícita no pai
        $parentRole = $parent->userRole($this);
        if (in_array($parentRole, [ProjectUserRole::ADMIN, ProjectUserRole::CONTRIBUTOR])) {
            return $parentRole;
        }

        return null;
    }

    public function isTaskAssignee(Task $task): bool
    {
        return $this->tasks()->where('task_id', $task->id)->exists();
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
