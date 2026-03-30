<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\ShowTaskAction;
use App\Http\Requests\Task\ShowTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $project = Project::findOrFail($request->query('project_id'));

        $this->authorize('create', [Task::class, $project]);

        return view('Task.create', compact('project'));
    }

    public function store(StoreTaskRequest $request, CreateTaskAction $action)
    {
        $task = $action->execute($request->validated(), Auth::id());

        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Tarefa criada com sucesso!');
    }

    public function show(ShowTaskRequest $request, Task $task, ShowTaskAction $action)
    {
        $task = $action->execute($task);

        return view('Task.show', compact('task'));
    }

    public function edit(Task $task)
    {
        //
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        //
    }

    public function destroy(Task $task)
    {
        //
    }
}
