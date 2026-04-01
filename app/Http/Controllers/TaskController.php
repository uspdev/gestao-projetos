<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function show(Task $task)
    {
        Gate::authorize('view', $task);
        $task = $task->load([
            'project:id,name,status',
            'users:id,name,email',
        ]);

        return view('Task.show', compact('task'));
    }

    public function edit(Task $task)
    {
        Gate::authorize('update', $task);
        return view('Task.edit', compact('task'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task = DB::transaction(function () use ($task, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $task->update($data);
        });

        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Tarefa atualizada com sucesso!');
    }

    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);
        DB::transaction(function () use ($task) {
            $task->delete();
        });

        return redirect()->route('projects.tasks.index', $task->project)
                         ->with('success', 'Tarefa excluida com sucesso!');
    }
}
