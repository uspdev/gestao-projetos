<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\Tag;

class ProjectTaskController extends Controller
{
    public function index(Project $project)
    {
        \UspTheme::activeUrl('meus-projetos');

        Gate::authorize('viewAny', [Task::class, $project]);
        $view = request()->query('view', 'kanban');
        $kanbanView = $view === 'kanban';
        $showDone = $kanbanView || request()->boolean('show_done');

        $tasks = $project->tasks()
            ->with('project')
            ->with('users')
            ->with('tags')
            ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy('priority', 'asc')
            ->latest()
            ->get();

        $availableTags = Tag::forProjects();

        $projectSelectedTags = collect(old('tags', $project->tagsWithType('projects')->pluck('id')->all()))
            ->map(fn($id) => (int) $id)
            ->all();

        $availableTaskTags = Tag::forTasks();

        $tasksSelectedTags = $tasks->mapWithKeys(function ($task) {
            return [$task->id => $task->tags->pluck('id')->all()];
        });

        return view('project-tasks.index', compact(
            'tasks',
            'project',
            'availableTags',
            'projectSelectedTags',
            'availableTaskTags',
            'tasksSelectedTags',
            'showDone',
            'kanbanView',
            'view'
        ));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Task::class, $project]);

        $availableTags = Tag::forTasks();

        return view('project-tasks.create', compact('project', 'availableTags'));
    }

    public function store(StoreTaskRequest $request, Project $project)
    {
        $task = DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['project_id'] = $project->id;
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? TaskStatus::TO_DO->value;

            $task = Task::create($data);
            $task->users()->attach(Auth::id());

            $task->syncTagsByIds($request->tags ?? null);

            return $task;
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Tarefa criada com sucesso!');
    }
}
