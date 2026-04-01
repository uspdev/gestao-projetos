<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Models\Project; 
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectTaskController extends Controller
{
    public function index(Project $project)
    {
        Gate::authorize('viewAny', [Task::class, $project]);
        $tasks = $project->tasks()
                         ->with('project:id,name,status')
                         ->with('users:id,name')
                         ->orderBy('priority', 'asc')
                         ->latest()
                         ->get();
        
        return view('Project.Task.index', compact('tasks', 'project'));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Task::class, $project]);

        return view('Task.create', compact('project'));
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

            return $task;
        });

        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Tarefa criada com sucesso!');
    }
}