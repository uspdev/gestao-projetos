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
use Illuminate\Http\Request;

class TaskController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('minhas-tasks');

            return $next($request);
        })->only('index');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        Gate::authorize('viewTasks', $user);

        $taskView = request()->query('view') ?? session('tasks_view', 'list'); //list ou kanban
        session(['tasks_view' => $taskView]);

        $showDone = $request->boolean('show_done');

        $tasks = $user->tasks()
            ->with([
                'project',
                'users',
            ])
            ->when(! $showDone, fn($query) => $query->where('status', '!=', \App\Enums\Task\TaskStatus::DONE->value))
            ->orderBy(
                \App\Models\Project::select('name')
                    ->whereColumn('projects.id', 'tasks.project_id')
            )
            ->latest()
            ->get();

        // O apontamento mudou para a pasta principal de tarefas
        return view('tasks.index', compact('tasks', 'user', 'showDone'));
    }

    public function show(Task $task)
    {
        Gate::authorize('view', $task);
        \UspTheme::activeUrl('meus-projetos');

        $task = $task->load([
            'project',
            'users',
            'tags',
        ]);

        return view('tasks.show', compact('task'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        DB::transaction(function () use ($task, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            // Decodifica as entidades HTML na
            // descrição para evitar que sejam armazenadas como texto literal
            if (array_key_exists('description', $data) && is_string($data['description'])) {
                $data['description'] = html_entity_decode($data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $task->update($data);

            $task->syncTagsByIds($request->tags ?? []);
        });

        if ($request->has('action')) {
            return redirect($request->action)
                ->with('alert-success', 'Tarefa atualizada com sucesso!');
        }

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Tarefa atualizada com sucesso!');
    }

    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);
        DB::transaction(function () use ($task) {
            $task->delete();
        });

        return redirect()->route('projects.show', $task->project)
            ->with('alert-success', 'Tarefa excluida com sucesso!');
    }

    /**
     * Atualiza o status de uma tarefa.
     *
     * @param  \App\Http\Requests\Task\UpdateTaskStatusRequest  $request
     * @param  \App\Models\Task  $task
     */
    public function updateTaskStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        DB::transaction(function () use ($task, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            if ($data['status'] === \App\Enums\Task\TaskStatus::DONE->value) {
                $data['completed_at'] = now();
            } else {
                $data['completed_at'] = null;
            }

            $task->update($data);
        });

        return back()
            ->with('alert-success', 'Status da tarefa atualizado com sucesso!');
    }

    /**
     * Atribui um usuário a uma tarefa.
     *
     * @param  \App\Http\Requests\Task\StoreTaskAssigneeRequest  $request
     * @param  \App\Models\Task  $task
     */
    public function storeAssignee(StoreTaskAssigneeRequest $request, Task $task)
    {
        $data = $request->validated();

        $user = User::query()->findOrFail($data['user_id']);

        if (!$user->isContributorOfProject($task->project)) {
            return redirect()->route('tasks.show', $task)
                ->with('alert-danger', 'Somente colaboradores do projeto podem ser atribuídos à tarefa.');
        }

        DB::transaction(function () use ($task, $user) {
            $task->users()->syncWithoutDetaching([$user->id]);
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Colaborador atribuído à tarefa com sucesso!');
    }

    /**
     * Retorna usuários selecionáveis para atribuir a uma tarefa.
     *
     * @param  \App\Models\Task  $task
     */
    public function selectableAssignees(Task $task)
    {
        Gate::authorize('storeAssignee', $task);

        $users = User::selectableToTask($task->id, $task->project_id)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Remove a atribuição de um usuário a uma tarefa.
     *
     * @param  \App\Models\Task  $task
     * @param  \App\Models\User  $user
     */
    public function destroyAssignee(Task $task, User $user)
    {
        Gate::authorize('storeAssignee', $task);

        DB::transaction(function () use ($task, $user) {
            $task->users()->detach($user->id);
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Membro removido da tarefa com sucesso!');
    }
}
