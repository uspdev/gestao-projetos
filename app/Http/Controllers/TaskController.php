<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\StoreTaskAssigneeRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
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
        })->only(['userIndex', 'projectIndex', 'create']);
    }

    /**
     * Lista as tarefas do usuario autenticado.
     */
    public function userIndex(Request $request)
    {
        $taskView = request()->query('view') ?? session('tasks_view', 'list'); //list ou kanban
        session(['tasks_view' => $taskView]);

        $showDone = $request->boolean('show_done');
        $viewAll = session('admin_view_all', false);

        $user = Auth::user();
        Gate::authorize('viewTasks', $user);

        // Se o usuário é admin e quer ver todas as tasks, buscar de todos os projetos
        if ($user->isAdmin() && $viewAll) {
            $tasksQuery = Task::query()
                ->with([
                    'project',
                    'users',
                ])
                ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
                ->orderBy(
                    Project::select('name')
                        ->whereColumn('projects.id', 'tasks.project_id')
                )
                ->latest();
        } else {
            $tasksQuery = $user->tasks()
                ->with([
                    'project',
                    'users',
                ])
                ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
                ->orderBy(
                    Project::select('name')
                        ->whereColumn('projects.id', 'tasks.project_id')
                )
                ->latest();
        }

        $tasks = $tasksQuery->get();
        $tasks = $this->filterTasksByEnabledModule($tasks);

        return view('module-tasks.user-index', compact('tasks', 'user', 'showDone', 'viewAll'));
    }

    /**
     * Lista as tarefas de um projeto especifico.
     */
    public function projectIndex(Request $request, Project $project)
    {
        $this->ensureTasksModuleEnabled($project);

        $taskView = $request->query('view') ?? session('tasks_view', 'list'); //list ou kanban
        session(['tasks_view' => $taskView]);

        $showDone = $request->boolean('show_done');

        Gate::authorize('viewAny', [Task::class, $project]);

        $tasks = $project->tasks()
            ->with('project')
            ->with('users')
            ->with('tags')
            ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy('completed_at', 'asc')
            ->orderBy('priority', 'asc')
            ->latest()
            ->get();

        return view('module-tasks.index', compact(
            'tasks',
            'project',
            'showDone'
        ));
    }


    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->ensureTasksModuleEnabled($project);

        $task = DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['project_id'] = $project->id;
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? TaskStatus::TO_DO->value;

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

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->ensureTasksModuleEnabled($task->project);

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
