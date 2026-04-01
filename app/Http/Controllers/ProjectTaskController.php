<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\IndexTaskAction;
use App\Http\Requests\Task\StoreTaskRequest;
use Illuminate\Http\Request;
use App\Models\Project; 
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectTaskController extends Controller
{
    public function index(Project $project, IndexTaskAction $action)
    {
        Gate::authorize('viewAny', [Task::class, $project]);
        $tasks = $action->execute($project);
        
        return view('Project.Task.index', compact('tasks', 'project'));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Task::class, $project]);

        return view('Task.create', compact('project'));
    }

    public function store(StoreTaskRequest $request, Project $project, CreateTaskAction $action)
    {
        $task = $action->execute($project, $request->validated(), Auth::id());

        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Tarefa criada com sucesso!');
    }
}