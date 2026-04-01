<?php

namespace App\Http\Controllers;

use App\Actions\Task\DestroyTaskAction;
use App\Actions\Task\ShowTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function show(Request $request, Task $task, ShowTaskAction $action)
    {
        $task = $action->execute($task);
        return view('Task.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);
        return view('Task.edit', compact('task'));
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action)
    {
        $task = $action->execute($task, $request->validated(), Auth::id());
        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Tarefa atualizada com sucesso!');
    }

    public function destroy(Request $request, Task $task, DestroyTaskAction $action)
    {
        $action->execute($task);

        return redirect()->route('projects.tasks.index', $task->project)
                         ->with('success', 'Tarefa excluida com sucesso!');
    }
}
