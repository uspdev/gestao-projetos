<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\StoreTaskAssigneeRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskDescriptionRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Mail\TaskAssigned;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Module;
use App\Models\Task;
use App\Models\User;
use App\Services\Mentions\MentionManager;
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

        $tasksDone = $request->query('tasks_done') ?? session('tasks_done', '0'); // 0 exibe ativas, 1 exibe concluídas
        session(['tasks_done' => $tasksDone]);

        $tasksMine = $request->query('tasks_mine') ?? session('tasks_mine', '0');
        session(['tasks_mine' => $tasksMine]);

        Gate::authorize('viewAny', [Task::class, $project]);

        $tasksByStatus = $project->tasks()
            ->with(['project', 'users', 'tags'])
            ->when($tasksMine, fn($query) => $query->whereHas('users', fn($userQuery) => $userQuery->where('users.id', Auth::id())))
            // Se $tasksDone for true (1), filtra para mostrar apenas tarefas com status DONE.
            // Caso contrário, filtra para mostrar apenas tarefas que não estão com status DONE.
            ->when(
                $tasksDone,
                fn($query) => $query->where('status', TaskStatus::DONE->value),
                fn($query) => $query->where('status', '!=', TaskStatus::DONE->value)
            )
            ->orderByPriority()
            ->when(
                $taskView === 'kanban',
                fn($query) => $query->orderBy('completed_at')
            )
            ->latest()
            ->get();

        if ($taskView === 'kanban') {
            $tasksByStatus = $tasksByStatus->groupBy('status');
        }

        $statuses = collect(TaskStatus::cases())
            ->when(
                $tasksDone,
                fn($collection) => $collection->filter(fn(TaskStatus $status) => $status === TaskStatus::DONE),
                fn($collection) => $collection->reject(fn(TaskStatus $status) => $status === TaskStatus::DONE)
            );

        return view('module-tasks.index', compact(
            'tasksByStatus',
            'statuses',
            'project',
        ));
    }


    public function store(StoreTaskRequest $request, Project $project, MentionManager $mentionManager)
    {
        $this->ensureTasksModuleEnabled($project);

        [$task, $assignee] = DB::transaction(function () use ($project, $request, $mentionManager) {
            $data = $request->validated();
            $assigneeId = $data['assignee_id'] ?? null;
            unset($data['assignee_id']);

            $data['project_id'] = $project->id;
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? TaskStatus::ASSIGNED->value;

            $task = Task::create($data);
            $assignee = $assigneeId ? User::query()->findOrFail($assigneeId) : null;

            if ($assignee) {
                $this->assignUser($task, $assignee);
            }

            $mentionManager->validateAllMentions($task, 'description', $data['description'] ?? null);
            $mentionManager->synchronize($task, 'description', $data['description'] ?? null);

            if ($request->has('tags')) {
                $tagsToSync = Tag::withType(Tag::TYPE_TASK)
                    ->whereIn('id', $request->tags)
                    ->get();
                $task->syncTagsWithType($tagsToSync, Tag::TYPE_TASK);
            }

            return [$task, $assignee];
        });

        if ($assignee) {
            $this->queueAssignmentNotification($task, $assignee);
        }

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
        $files = $task->media()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'files_page');
        $links = \Illuminate\Support\Facades\Schema::hasTable('links')
            ? $task->links()
                ->with('creator')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'links_page')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);


        return view('module-tasks.show', compact('task', 'project', 'files', 'links'));
    }

    /**
     * Atualiza apenas a descrição da tarefa.
     *
     * @param  \App\Http\Requests\Task\UpdateTaskRequest  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDescription(UpdateTaskDescriptionRequest $request, Task $task, MentionManager $mentionManager)
    {
        $this->ensureTasksModuleEnabled($task->project);

        DB::transaction(function () use ($task, $request, $mentionManager) {
            $description = $request->input('description', '');
            if (is_string($description)) {
                $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $mentionManager->validateNewMentions($task, 'description', $description);
            $task->update([
                'description' => $description,
                'updated_by' => Auth::id(),
            ]);
            $mentionManager->synchronize($task, 'description', $description);
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

            if ($data['status'] === TaskStatus::DONE->value) {
                $data['completed_at'] = now();
            } else {
                $data['completed_at'] = null;
            }
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

        DB::transaction(function () use ($task, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            if ($data['status'] === TaskStatus::DONE->value) {
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
        $this->ensureTasksModuleEnabled($task->project);

        $data = $request->validated();

        $user = User::query()->findOrFail($data['user_id']);
        if (!$user->isContributorOfProject($task->project)) {
            return redirect()->route('tasks.show', $task)
                ->with('alert-danger', 'Somente colaboradores do projeto podem ser atribuídos à tarefa.');
        }

        $newlyAssigned = DB::transaction(function () use ($task, $user): bool {
            return $this->assignUser($task, $user);
        });

        if ($newlyAssigned) {
            $this->queueAssignmentNotification($task, $user);
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

    /**
     * Vincula um responsável e aplica a transição inicial de status da tarefa.
     */
    private function assignUser(Task $task, User $user): bool
    {
        $alreadyAssigned = $task->users()->where('users.id', $user->id)->exists();

        $task->users()->syncWithoutDetaching([$user->id]);

        if ($task->status === TaskStatus::NEW) {
            $task->update([
                'status' => TaskStatus::ASSIGNED,
            ]);
        }

        return ! $alreadyAssigned;
    }

    /**
     * Enfileira o envio de um e-mail de notificação para o usuário atribuído à tarefa.
     * E-mail será enviado diretamente para fila de envio, sem tempo de digestão. 
     */
    private function queueAssignmentNotification(Task $task, User $user): void
    {
        $actor = Auth::user();

        if (! $actor || $actor->id === $user->id) {
            return;
        }

        $task->loadMissing('project');
        Mail::to($user->email)->queue(new TaskAssigned($user, $actor, $task));
    }

    // Filtra as tarefas para retornar apenas aquelas cujo projeto tem o módulo de tarefas habilitado
    private function filterTasksByEnabledModule(Collection $tasks): Collection
    {
        return $tasks
            ->filter(fn(Task $task) => $task->project?->isModuleEnabled('tasks'))
            ->values();
    }
}
