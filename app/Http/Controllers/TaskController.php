<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\StoreTaskAssigneeRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskDescriptionRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Mail\TaskAssigned;
use App\Mail\TaskCompleted;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Module;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TaskController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->route()?->hasParameter('project')) {
                \UspTheme::activeUrl('meus-projetos');
            } else {
                \UspTheme::activeUrl('minhas-tasks');
            }

            return $next($request);
        })->only(['indexUser', 'indexProject', 'create']);
    }

    /**
     * Lista as tarefas de um projeto especifico.
     */
    public function indexProject(Request $request, Project $project)
    {
        $this->ensureTasksModuleEnabled($project);

        $taskView = $request->query('view') ?? session('tasks_view', 'list'); //list ou kanban
        session(['tasks_view' => $taskView]);

        $tasksDone = $request->query('tasks_done') ?? session('tasks_done', '1'); //list ou kanban
        session(['tasks_done' => $tasksDone]);

        $tasksMine = $request->query('tasks_mine') ?? session('tasks_mine', '0');
        session(['tasks_mine' => $tasksMine]);

        Gate::authorize('viewAny', [Task::class, $project]);

        $tasksByStatus = $project->tasks()
            ->with(['project', 'users', 'tags'])
            ->when($tasksMine, fn($query) => $query->whereHas('users', fn($userQuery) => $userQuery->where('users.id', Auth::id())))
            ->when(! $tasksDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy('completed_at', 'asc')
            ->orderBy('priority', 'asc')
            ->latest()
            ->get();

        $tasksCount = $tasksByStatus->count();

        if ($taskView === 'kanban') {
            $tasksByStatus = $tasksByStatus->groupBy('status');
        }

        $statuses = collect(TaskStatus::cases())
            ->when(!$tasksDone, fn($collection) => $collection->reject(
                fn(TaskStatus $status) => $status === TaskStatus::DONE
            ));

        return view('module-tasks.index', compact(
            'tasksByStatus',
            'statuses',
            'project',
            'tasksCount',
        ));
    }


    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->ensureTasksModuleEnabled($project);

        $task = DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['project_id'] = $project->id;
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? TaskStatus::ASSIGNED->value;

            $task = Task::create($data);

            if ($request->has('tags')) {
                $tagsToSync = Tag::withType(Tag::TYPE_TASK)
                    ->whereIn('id', $request->tags)
                    ->get();
                $task->syncTagsWithType($tagsToSync, Tag::TYPE_TASK);
            }

            return $task;
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Tarefa criada com sucesso!');
    }

    public function show(Task $task)
    {
        $this->ensureTasksModuleEnabled($task->project);

        Gate::authorize('view', $task);
        \UspTheme::activeUrl('meus-projetos');

        $task = $task->load([
            'project',
            'users',
            'tags',
        ]);
        $project = $task->project;


        return view('module-tasks.show', compact('task', 'project'));
    }

    /**
     * Atualiza apenas a descrição da tarefa.
     *
     * @param  \App\Http\Requests\Task\UpdateTaskRequest  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDescription(UpdateTaskDescriptionRequest $request, Task $task)
    {
        $this->ensureTasksModuleEnabled($task->project);

        DB::transaction(function () use ($task, $request) {
            $description = $request->input('description', '');
            if (is_string($description)) {
                $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $task->update([
                'description' => $description,
                'updated_by' => Auth::id(),
            ]);
        });

        if ($request->has('action')) {
            return redirect($request->action)
                ->with('alert-success', 'Descrição da tarefa atualizada com sucesso!');
        }

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Descrição da tarefa atualizada com sucesso!');
    }

    /**
     * Atualiza as demais informações da tarefa (título, status, prioridade, datas e tags).
     *
     * @param  \App\Http\Requests\Task\UpdateTaskRequest  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateInfo(UpdateTaskRequest $request, Task $task)
    {
        $this->ensureTasksModuleEnabled($task->project);

        DB::transaction(function () use ($task, $request) {
            $data = $request->only(['title', 'status', 'priority', 'start_date', 'due_date']);
            $data['updated_by'] = Auth::id();

            $task->update($data);

            $task->syncTagsByIds($request->tags ?? []);
        });

        if ($request->has('action')) {
            return redirect($request->action)
                ->with('alert-success', 'Informações da tarefa atualizadas com sucesso!');
        }

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Informações da tarefa atualizadas com sucesso!');
    }

    public function destroy(Task $task)
    {
        $this->ensureTasksModuleEnabled($task->project);

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
        $this->ensureTasksModuleEnabled($task->project);

        $previousStatus = $task->status;

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

        $data = $request->validated();
        // Se a tarefa foi marcada como concluída agora, e antes não estava,
        // enviar notificações para os usuários associados à tarefa, exceto para o próprio ator
        if ($previousStatus !== TaskStatus::DONE && $data['status'] === TaskStatus::DONE->value) {
            $actor = Auth::user();
            $task->load(['users', 'project']);

            $task->users
                ->unique('id')
                ->filter(fn(User $user) => !$actor || $user->id !== $actor->id)
                ->each(function (User $user) use ($actor, $task) {
                    Mail::to($user->email)->queue(new TaskCompleted($user, $actor, $task));
                });
        }

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
        $this->ensureTasksModuleEnabled($task->project);

        $data = $request->validated();

        $user = User::query()->findOrFail($data['user_id']);

        $alreadyAssigned = $task->users()->where('users.id', $user->id)->exists();

        if (!$user->isContributorOfProject($task->project)) {
            return redirect()->route('tasks.show', $task)
                ->with('alert-danger', 'Somente colaboradores do projeto podem ser atribuídos à tarefa.');
        }

        DB::transaction(function () use ($task, $user) {
            $task->users()->syncWithoutDetaching([$user->id]);

            if($task->status === TaskStatus::NEW) {
                $task->update([
                    'status' => TaskStatus::ASSIGNED
                ]);
            }
        });

        $actor = Auth::user();
        // Envia notificacao para o usuario atribuido, exceto para o proprio ator,
        // e somente se ele ainda nao estava atribuido à tarefa
        if (! $alreadyAssigned && $actor && $actor->id !== $user->id) {
            $task->loadMissing('project');
            Mail::to($user->email)->queue(new TaskAssigned($user, $actor, $task));
        }

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
        $this->ensureTasksModuleEnabled($task->project);

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
        $this->ensureTasksModuleEnabled($task->project);

        Gate::authorize('storeAssignee', $task);

        DB::transaction(function () use ($task, $user) {
            $task->users()->detach($user->id);
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Membro removido da tarefa com sucesso!');
    }

    // Verifica se o módulo de tarefas está habilitado para o projeto
    private function ensureTasksModuleEnabled(Project $project): void
    {
        abort_unless(Module::isEnabledForProject($project, 'tasks'), 403);
    }

    // Filtra as tarefas para retornar apenas aquelas cujo projeto tem o módulo de tarefas habilitado
    private function filterTasksByEnabledModule(Collection $tasks): Collection
    {
        return $tasks
            ->filter(fn(Task $task) => $task->project?->isModuleEnabled('tasks'))
            ->values();
    }
}
