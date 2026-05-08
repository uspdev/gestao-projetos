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

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        })->only(['index', 'create']);
    }

    public function index(Project $project)
    {
        Gate::authorize('viewAny', [Task::class, $project]);
        $taskView = request()->query('view') ?? session('tasks_view', 'list'); //list ou kanban
        session(['tasks_view' => $taskView]);

        $showDone = request()->boolean('show_done');

        $tasks = $project->tasks()
            ->with('project')
            ->with('users')
            ->with('tags')
            ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy('priority', 'asc')
            ->latest()
            ->get();

        return view('project-tasks.index', compact(
            'tasks',
            'project',
            'showDone',
            // 'kanbanView',
            // 'taskView'
        ));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Task::class, $project]);

        return view('project-tasks.create', compact('project'));
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

            if ($request->has('tags')) {
                $tagsToSync = Tag::withType('tasks')
                    ->whereIn('id', $request->tags)
                    ->get();
                $task->syncTagsWithType($tagsToSync, 'tasks');
            }

            return $task;
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Tarefa criada com sucesso!');
    }
}
