<?php

namespace App\Http\Controllers;

use App\Contracts\Watchable;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('dashboard');
            return $next($request);
        })->only(['show']);
    }

    public function show(User $user, Request $request)
    {
        Gate::authorize('view', $user);

        $taskViewDefault = 'kanban';  //list ou kanban
        $taskView = request()->query('view', session('tasks_view', $taskViewDefault));
        session(['tasks_view' => $taskView]);

        $taskDoneDefault = 0; // 0 não exibe, ou 1 exibe tarefas concluídas
        $tasksDone = $request->query('tasks_done', session('tasks_done', $taskDoneDefault));
        session(['tasks_done' => $tasksDone]);

        $user->load([
            'roles',
            'projects',
            'tasks' => fn($query) => $query->withTasksModuleEnabled(),
            'tasks.project',
            'tasks.tags',
        ]);

        if ($user->projects->isEmpty()) {
            return view('projects.index-no-project');
        }

        $tasksByStatus = $user->tasksByStatus($taskView, $tasksDone);
        $availableMeetingProjectIds = Project::availableForMeetings($user)->pluck('id');
        $meetings = $user->scheduledMeetings($availableMeetingProjectIds);
        $watchedResources = $request->user()->is($user)
            ? $this->watchedResourcesFor($user)
            : collect();

        return view('users.show', compact(
            'user',
            'tasksByStatus',
            'meetings',
            'availableMeetingProjectIds',
            'watchedResources',
        ));
    }

    /**
     * Recursos acompanhados que continuam visíveis para o dono da dashboard.
     *
     * @return Collection<int, array{
     *     watch: Watch,
     *     resource: Watchable,
     *     type: string,
     *     label: string,
     *     context: string|null,
     *     url: string
     * }>
     */
    private function watchedResourcesFor(User $user): Collection
    {
        if (! Schema::hasTable('watches')) {
            return collect();
        }

        return $user->watches()
            ->with(['watchable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Project::class => ['users', 'parent.users'],
                    Task::class => ['project.users', 'project.parent.users'],
                    Meeting::class => ['projects.users', 'projects.parent.users'],
                ]);
            }])
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(function (Watch $watch) use ($user): ?array {
                $resource = $watch->watchable;

                if (! $resource instanceof Watchable || ! $resource->watchCanBeViewedBy($user)) {
                    return null;
                }

                $type = match (true) {
                    $resource instanceof Project => 'project',
                    $resource instanceof Task => 'task',
                    $resource instanceof Meeting => 'meeting',
                    default => null,
                };

                if (! $type) {
                    return null;
                }

                $context = null;
                $url = $resource->watchUrl();

                if ($resource instanceof Task) {
                    $context = $resource->project?->name;
                }

                if ($resource instanceof Meeting) {
                    $contextProject = $resource->projects
                        ->sortBy('name')
                        ->first(fn(Project $project): bool => $project->isModuleEnabled('meetings')
                            && $user->isViewerOfProject($project));

                    if (! $contextProject) {
                        return null;
                    }

                    $context = $contextProject->name;
                    $url = route('projects.meetings.show', [$contextProject, $resource]);
                }

                if (! $url) {
                    return null;
                }

                return [
                    'watch' => $watch,
                    'resource' => $resource,
                    'type' => $type,
                    'label' => $resource->watchLabel(),
                    'context' => $context,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values();
    }
}
