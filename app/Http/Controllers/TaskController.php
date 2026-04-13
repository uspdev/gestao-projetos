<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskAssigneeRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Models\User;
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

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        Gate::authorize('update', $task);
        return view('tasks.edit', compact('task'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        DB::transaction(function () use ($task, $request) {
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

        return redirect()->route('projects.show', $task->project)
                         ->with('success', 'Tarefa excluida com sucesso!');
    }

    public function updateTaskStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        DB::transaction(function () use ($task, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $task->update($data);
        });

        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Status da tarefa atualizado com sucesso!');
    }

    public function storeAssignee(StoreTaskAssigneeRequest $request, Task $task)
    {
        $data = $request->validated();

        $user = User::query()->findOrFail($data['user_id']);

        if (!$user->isMemberOfProject($task->project)) {
            return redirect()->route('tasks.show', $task)
                ->with('error', 'Somente membros do projeto podem ser atribuídos à tarefa.');
        }

        DB::transaction(function () use ($task, $user) {
            $task->users()->syncWithoutDetaching([$user->id]);
        });

        return redirect()->route('tasks.show', $task);
    }

    public function selectableAssignees(Task $task)
    {
        Gate::authorize('storeAssignee', $task);

        $users = User::selectableToTask($task->id, $task->project_id)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function destroyAssignee(Task $task, User $user)
    {
        Gate::authorize('storeAssignee', $task);

        DB::transaction(function () use ($task, $user) {
            $task->users()->detach($user->id);
        });

        return redirect()->route('tasks.show', $task);
    }
}
