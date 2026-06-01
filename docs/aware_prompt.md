# Aware Prompt - Sistema de Gestao de Projetos USP (2026-05-27)

## Papel do LLM
- Voce e um Engenheiro de Software Senior, especialista em PHP e Laravel, ajudando um sistema interno de gestao de projetos.
- Responda sempre em PT-BR, de forma direta, e sugira a opcao mais simples primeiro.
- Baseie TODAS as respostas estritamente neste contexto. Se algo nao estiver aqui, pergunte antes de assumir.

## Visao geral do sistema
- Sistema interno para gestao unificada de projetos, tarefas e reunioes.
- Usado por devs e por pessoas nao tecnicas; clareza e simplicidade sao essenciais, sem perder extensibilidade.
- O MVP ja inclui projetos, tarefas e reunioes, com suporte a comentarios, tags e auditoria.

## Stack e dependencias
- PHP 8.2, Laravel 12, Laravel Sanctum.
- Autenticacao via uspdev/senhaunica-socialite.
- Roles/permissions via spatie/laravel-permission (User usa HasRoles).
- Tags via spatie/laravel-tags (com modelo Tag extendido).
- Activity log via spatie/laravel-activitylog (model custom ActivityLog).
- Tema base: uspdev/laravel-usp-theme.
- Markdown: league/commonmark + spatie/commonmark-highlighter (helper md2html).

## Arquitetura e modulos (alto nivel)
- Modulos principais: Projetos, Tarefas, Reunioes, Comentarios, Tags, Tipos de Projeto, Fases.
- Modulos podem ser habilitados/desabilitados por projeto (ex.: tasks, meetings) via ProjectType e ProjectModule.
- Subprojetos: projetos podem ter parent_id para hierarquia simples (1 nivel).
- Comentarios e Itens de Pauta usam relacionamentos polimorficos.

## Entidades e relacionamentos

### Project
- Modelo: App\Models\Project
- Traits: HasTags, HasSlug, Auditable, SoftDeletes.
- Campos principais: name, slug, status, description, project_type_id, parent_id, visibility, permission_inheritance, phase_id.
- Relacionamentos:
  - users (N-N) via ProjectUser (pivot com role e pinned).
  - tasks (1-N).
  - meetings (N-N) via meeting_projects.
  - comments (morphMany).
  - meetingItems (morphMany) via discussable.
  - projectType (N-1) e phase (N-1).
  - parent (N-1) e children (1-N).
- Regras:
  - Slug vem de name e e unico.
  - Ao deletar (soft delete), ajusta slug para evitar conflito.
  - Deletar projeto soft-deleta tasks relacionadas (marca deleted_via_project); restore reverte.
  - Modulos ativos sao resolvidos por Module::isEnabledForProject().

### Task
- Modelo: App\Models\Task
- Traits: HasTags, Auditable, SoftDeletes.
- Campos principais: project_id, title, description, priority, status, start_date, due_date, completed_at.
- Relacionamentos: project (N-1), users (N-N via TaskUser), comments (morphMany), meetingItems (morphMany).
- Regras:
  - Modulo tasks precisa estar habilitado no projeto.
  - Task DONE fica bloqueada para update/delete (TaskPolicy).
  - Update de status para DONE preenche completed_at; status diferente limpa completed_at.
  - Tags sao do tipo tasks.

### Meeting
- Modelo: App\Models\Meeting
- Traits: Auditable, SoftDeletes.
- Campos principais: title, scheduled_at, location, notes, status.
- Relacionamentos: projects (N-N), meetingItems (1-N), comments (morphMany).
- Regras:
  - Modulo meetings precisa estar habilitado no projeto.
  - Meeting pertence a projeto ou ao projeto pai (no caso de subprojetos) para autorizacao.
  - Meeting items sao ordenados e nao podem ser removidos quando status = COMPLETED.

### MeetingItem
- Modelo: App\Models\MeetingItem
- Campos: meeting_id, discussable_type, discussable_id, order, notes.
- discussable: Project ou Task (morphTo).
- comments: morphMany.

### Comment
- Modelo: App\Models\Comment
- Campos: user_id, commentable_type, commentable_id, parent_id, text, is_active.
- Regras:
  - Delete e soft (is_active = false).
  - Update e delete apenas pelo autor e se is_active.
  - Notifica recipients definidos pelo modelo comentado.

### Tag
- Modelo: App\Models\Tag (extende Spatie Tag)
- Tipos: projects e tasks.
- Campos extras: color, description.

### ProjectType e Phase
- ProjectType define configuracao default de modulos (project_type_modules) e fases (project_type_phases).
- Phase representa etapa do projeto (com cor e flags is_initial/is_final).

### Module
- Define recursos habilitaveis por projeto.
- Resolucao de habilitacao considera:
  - defaults do ProjectType (enabled/required/editable)
  - overrides do Project (ProjectModule)
  - required sempre prevalece sobre disable.

## Enums (valores atuais)

### ProjectStatus
- DRAFT, PLANNED, ACTIVE, HOLD, COMPLETED, CANCELLED, ARCHIVED.

### ProjectUserRole
- ADMIN, CONTRIBUTOR, VIEWER.

### ProjectPermissionInheritance
- NONE, READ, FULL.

### ProjectVisibility
- PUBLIC, AUTHENTICATED, PRIVATE.

### TaskStatus
- NEW, ASSIGNED, IN_PROGRESS, IN_REVIEW, HOLD, DONE.

### TaskPriority
- URGENT(1), HIGH(2), MEDIUM(3), LOW(4).

### MeetingStatus
- SCHEDULED, ONGOING, COMPLETED.

## Autenticacao e autorizacao
- Autenticacao via Senha Unica Socialite (User usa HasSenhaunica).
- User::isAdmin() = hasRole('admin') OR can('admin').
- Policies:
  - ProjectPolicy: admin override; view para membros; create via gates senhaunica.*; update para contributor; delete/storeMember para admin.
  - TaskPolicy: nao libera admin global (before comentado). Depende de modulo tasks e roles. Update/delete bloqueados se DONE.
  - MeetingPolicy: depende de modulo meetings e relacao com o projeto (ou pai).
  - CommentPolicy: usa can('view'/'comment') no commentable; edit/delete so autor.
  - UserPolicy: admin override; view permitido; viewTasks apenas para o proprio user.
- Gates usados nos controllers com Gate::authorize(...).

## Regras de negocio importantes
- Membros de projeto:
  - Pivot ProjectUser possui role e pinned.
  - Nao remover o ultimo ADMIN do projeto.
  - Admin do projeto pai deve permanecer ADMIN no subprojeto.
- Subprojetos:
  - Apenas projeto organizacional (slug = "organizacional") pode vincular subprojetos.
  - Subprojeto candidato deve ser projeto raiz, sem filhos, e compartilhar ao menos um admin com o pai.
  - Nao permite subprojeto organizacional dentro de outro organizacional.
- Heranca de permissao em subprojetos:
  - READ ou FULL permite herdar view do projeto pai.
  - FULL permite herdar contributor do projeto pai.

## Auditoria e logs
- Trait Auditable preenche created_by/updated_by/deleted_by.
- Ao deletar, se tiver slug, adiciona "-deleted-<timestamp>".
- Activity log centralizado em config/projetos.php (retencao 365 dias).
- ActivityLog custom model com scopes.
- PivotAuditSubscriber registra updateExistingPivot em pivots (ProjectUser, TaskUser, MeetingProject, ProjectModule, ProjectTypeModule, ProjectTypePhase).
- TaggablePivot registra attach/detach de tags.

## Comentarios e notificacoes
- Interface HasCommentRecipients define destinatarios de comentarios por modelo.
- Notificacoes por email (queue) em:
  - ProjectUserAdded, TaskAssigned, TaskCompleted
  - MeetingCreated, MeetingUpdated
  - ProjectLinkedAsSubproject, ProjectUnlinkedAsSubproject
  - NewComment

## Rotas principais (web.php)
- Projetos:
  - CRUD via resource projects (sem edit/update) + rotas patch para status, phase, visibility, permission-inheritance, name, slug, description, tags, pin, modules.
  - Subprojetos: selectable, link, unlink.
  - Membros: listar selecionaveis, store, updateRole, destroy.
- Tarefas:
  - index do usuario: /tasks
  - index do projeto: /projects/{project}/tasks
  - status patch: /tasks/{task}/status
  - assignees: selectable, store, destroy
- Reunioes:
  - /projects/{project}/meetings (resource)
  - items: store, destroy, update notes
  - update status
- Comments: store, update, destroy.
- Admin: resource admin com middleware can:admin.

## UI e views
- Views em resources/views:
  - projects/, module-tasks/, module-meetings/, module-phases/, users/, admin/, comments/, emails/.
- Tema via laravel-usp-theme; controllers ajustam activeUrl para menu.
- Listas de tarefas suportam list/kanban e filtros (show_done, tasks_done, tasks_mine).

## Convencoes de codigo
- Validacao via FormRequest (pastas por dominio: Project, Task, Meeting, Comment, MeetingItem, Phases).
- Controllers contem a logica de caso de uso (nao ha camada Action separada no codigo atual).
- Operacoes com varias escritas usam DB::transaction.
- Redirects com flash messages (alert-success / alert-danger).

## Roadmap (futuro, ainda nao implementado)
- Status updates periodicos.
- Documentacao interna com entidade propia.
- Midias em tarefas/comentarios.
- Dashboards e calendario (projeto e pessoal).
- Integracao bidirecional com GitHub.
- Estruturas: folders, lists, subtasks e breadcrumbs dinamicos.
- Views diferentes por perfil (dev vs admin).
- Auditoria avancada (historico completo de mudancas).
- Central de comunicacao (inbox, mencoes, emails).
- Busca global e filtros avancados.
- Atalhos de teclado globais.

## Guardrails para contribuicao
- Nunca assumir features fora deste contexto.
- Sempre verificar se modulo (tasks/meetings) esta habilitado antes de operar.
- Respeitar policies e Gate::authorize.
- Nao remover o ultimo admin do projeto.
- Manter uso de FormRequest para validacao.
- Manter auditoria (created_by/updated_by) ao criar/alterar.

## Snippets de codigo essenciais (exemplos reais do repo)
- Objetivo: mostrar o padrao real de Model, Request, Controller, Policy, Routes, Enum, Mail e modulo.
- Trechos abaixo sao recortes fieis do codigo atual; partes nao essenciais foram omitidas para caber no prompt.

### Model: Project (traits, casts, pivot com role/pinned)
```php
class Project extends Model implements Discussable, HasCommentRecipients
{
  use HasFactory, SoftDeletes, Auditable, HasTags, HasSlug;

  public const ORGANIZATIONAL_TYPE_SLUG = 'organizacional';

  protected $fillable = [
    'name',
    'slug',
    'status',
    'description',
    'project_type_id',
    'parent_id',
    'visibility',
    'permission_inheritance',
    'phase_id',
  ];

  protected array $roleByUserCache = [];
  protected string $slugSourceColumn = 'name';

  protected function casts(): array
  {
    return [
      'status' => ProjectStatus::class,
      'visibility' => ProjectVisibility::class,
      'permission_inheritance' => ProjectPermissionInheritance::class,
      'phase_id' => 'integer',
    ];
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class)
      ->using(ProjectUser::class)
      ->withPivot('role', 'pinned')
      ->withTimestamps()
      ->orderBy('name');
  }
}
```

### Model: Task (casts, escopos por modulo)
```php
class Task extends Model implements Discussable, HasCommentRecipients
{
  use HasFactory, SoftDeletes, Auditable, HasTags;

  protected $fillable = [
    'project_id',
    'title',
    'description',
    'priority',
    'status',
    'start_date',
    'due_date',
    'completed_at'
  ];

  protected function casts(): array
  {
    return [
      'status' => TaskStatus::class,
      'start_date' => 'date',
      'due_date' => 'date',
      'completed_at' => 'datetime',
      'priority' => TaskPriority::class
    ];
  }

  public function project(): BelongsTo
  {
    return $this->belongsTo(Project::class);
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class)
      ->using(TaskUser::class)
      ->withTimestamps();
  }

  public function scopeWithEnabledProjectModule(Builder $query, string $slug): Builder
  {
    $normalized = strtolower(trim($slug));
    if ($normalized === '') {
      return $query->whereRaw('1 = 0');
    }

    return $query->whereHas('project.modules', function (Builder $moduleQuery) use ($normalized) {
      $moduleQuery
        ->where('modules.slug', $normalized)
        ->where('project_modules.enabled', true);
    });
  }

  public function scopeWithTasksModuleEnabled(Builder $query): Builder
  {
    return $query->withEnabledProjectModule('tasks');
  }
}
```

### Trait: Auditable (created_by/updated_by/deleted_by + ajuste de slug)
```php
trait Auditable
{
  public static function bootAuditable()
  {
    static::creating(function ($model) {
      $model->created_by = self::getCurrentUserId();
      $model->updated_by = self::getCurrentUserId();
    });

    static::updating(function ($model) {
      $model->updated_by = self::getCurrentUserId();
    });

    static::deleting(function ($model) {
      $model->deleted_by = self::getCurrentUserId();
      if (method_exists($model, 'slugColumn')) {
        $slugColumn = $model->slugColumn();
        $currentSlug = $model->getAttribute($slugColumn);

        if (!empty($currentSlug) && !str_contains($currentSlug, '-deleted-')) {
          $model->setAttribute($slugColumn, $currentSlug . '-deleted-' . time());
        }
      }
      $model->saveQuietly();
    });
  }

  protected static function getCurrentUserId()
  {
    if (Auth::check()) {
      return Auth::id();
    }

    return null; // Retorna null para ações feitas pelo sistema (ex: CRON jobs)
  }
}
```

### Modulos: resolucao de habilitacao (Module::isEnabledForProject)
```php
class Module extends Model
{
  public static function isEnabledForProject(Project $project, string $moduleSlug): bool
  {
    $moduleSlug = strtolower(trim($moduleSlug));

    if ($moduleSlug === '') {
      return false;
    }

    $projectOverride = self::resolveProjectOverride($project->id, $moduleSlug);
    $projectTypeDefault = self::resolveProjectTypeDefault($project, $moduleSlug);

    if ($projectOverride !== null) {
      if (($projectTypeDefault['required'] ?? false) === true && $projectOverride === false) {
        return true;
      }

      return $projectOverride;
    }

    if ($projectTypeDefault !== null) {
      return (bool) $projectTypeDefault['enabled'];
    }

    return false;
  }

  private static function resolveProjectOverride(int $projectId, string $moduleSlug): ?bool
  {
    if (!Schema::hasTable('project_modules') || !Schema::hasTable('modules')) {
      return null;
    }

    return ProjectModule::query()
      ->where('project_id', $projectId)
      ->whereHas('module', fn($query) => $query->where('slug', $moduleSlug))
      ->value('enabled');
  }

  private static function resolveProjectTypeDefault(Project $project, string $moduleSlug): ?array
  {
    if (!Schema::hasTable('project_type_modules') || !Schema::hasTable('modules')) {
      return null;
    }

    if (!Schema::hasColumn('projects', 'project_type_id')) {
      return null;
    }

    $projectTypeId = (int) ($project->project_type_id ?? 0);
    if ($projectTypeId <= 0) {
      return null;
    }

    $config = $project->projectTypeModuleConfig($moduleSlug);

    if (! $config) {
      return null;
    }

    return [
      'enabled' => (bool) ($config['enabled'] ?? false),
      'required' => (bool) ($config['required'] ?? false),
      'editable' => (bool) ($config['editable'] ?? true),
    ];
  }
}
```

### FormRequest: StoreTaskRequest (authorize + rules)
```php
class StoreTaskRequest extends FormRequest
{
  public function authorize(): bool
  {
    $project = $this->route('project');

    return $this->user()->can('create', [Task::class, $project]);
  }

  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'min:3', 'max:120'],
      'description' => ['nullable', 'string', 'max:10000'],
      'priority' => ['nullable', Rule::enum(TaskPriority::class)],
      'status' => ['required', Rule::enum(TaskStatus::class)],
      'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
      'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],

      'tags' => ['nullable', 'array'],
      'tags.*' => ['integer', 'exists:tags,id'],
    ];
  }
}
```

### Policy: TaskPolicy (modulo habilitado + lock)
```php
class TaskPolicy
{
  public function viewAny(User $user, Project $project): bool
  {
    return $this->tasksModuleEnabled($project) && $user->isViewerOfProject($project);
  }

  public function view(User $user, Task $task): bool
  {
    return $this->tasksModuleEnabled($task->project) && $user->isViewerOfProject($task->project);
  }

  public function create(User $user, Project $project): bool
  {
    return $this->tasksModuleEnabled($project) && $user->isContributorOfProject($project);
  }

  public function update(User $user, Task $task): bool
  {
    if (! $this->tasksModuleEnabled($task->project)) {
      return false;
    }

    if ($task->isLocked()) {
      return false;
    }
    return $user->isAdminOfProject($task->project) || $user->isTaskAssignee($task);
  }

  public function delete(User $user, Task $task): bool
  {
    if (! $this->tasksModuleEnabled($task->project)) {
      return false;
    }

    if ($task->isLocked()) {
      return false;
    }
    return $user->isAdminOfProject($task->project)
      || $user->isTaskCreator($task);
  }

  public function storeAssignee(User $user, Task $task): bool
  {
    if (! $this->tasksModuleEnabled($task->project)) {
      return false;
    }

    if ($task->isLocked()) {
      return false;
    }
    return $user->isAdminOfProject($task->project) || $user->isTaskCreator($task);
  }

  public function comment(User $user, Task $task): bool
  {
    return $this->tasksModuleEnabled($task->project)
      && $user->isContributorOfProject($task->project);
  }

  public function UpdateStatus(User $user, Task $task): bool
  {
    if (! $this->tasksModuleEnabled($task->project)) {
      return false;
    }

    if ($task->isLocked()) {
      return true;
    }
    return false;
  }

  private function tasksModuleEnabled(Project $project): bool
  {
    return Module::isEnabledForProject($project, 'tasks');
  }
}
```

### Controller: TaskController (Gate + transacao + modulo habilitado)
```php
public function indexProject(Request $request, Project $project)
{
  $this->ensureTasksModuleEnabled($project);

  $taskView = $request->query('view') ?? session('tasks_view', 'list');
  session(['tasks_view' => $taskView]);

  $tasksDone = $request->query('tasks_done') ?? session('tasks_done', '1');
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

public function updateTaskStatus(UpdateTaskStatusRequest $request, Task $task)
{
  $this->ensureTasksModuleEnabled($task->project);

  $previousStatus = $task->status;

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

  $data = $request->validated();
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

private function ensureTasksModuleEnabled(Project $project): void
{
  abort_unless(Module::isEnabledForProject($project, 'tasks'), 403);
}
```

### Routes: tarefas (routes/web.php)
```php
// Tarefas do projeto
Route::get('projects/{project}/tasks', [TaskController::class, 'indexProject'])->name('projects.tasks.index');
Route::resource('projects.tasks', TaskController::class)->except([
  'index',
  'show',
  'edit',
  'update',
  'destroy'
]);

// Tarefas (Acoes Diretas)
Route::get('tasks', [TaskController::class, 'indexUser'])->name('tasks.index');
Route::patch('tasks/{task}/status', [TaskController::class, 'updateTaskStatus'])->name('tasks.updateTaskStatus');
Route::patch('tasks/{task}/description', [TaskController::class, 'updateDescription'])->name('tasks.updateDescription');
Route::patch('tasks/{task}/info', [TaskController::class, 'updateInfo'])->name('tasks.updateInfo');

Route::resource('tasks', TaskController::class)->except([
  'index',
  'create',
  'store',
  'edit',
  'update'
]);

Route::controller(TaskController::class)
  ->prefix('tasks/{task}/assignees')
  ->name('tasks.assignees.')
  ->group(function () {
    Route::get('selectable', 'selectableAssignees')->name('selectable');
    Route::post('/', 'storeAssignee')->name('store');
    Route::delete('{user}', 'destroyAssignee')->name('destroy');
  });
```

### Pivot: ProjectUser
```php
class ProjectUser extends Pivot
{
  protected $casts = [
    'role' => ProjectUserRole::class,
    'pinned' => 'bool',
  ];
}
```

### Enum: TaskStatus
```php
enum TaskStatus: string
{
  case NEW = 'NEW';
  case ASSIGNED = 'ASSIGNED';
  case IN_PROGRESS = 'IN_PROGRESS';
  case IN_REVIEW = 'IN_REVIEW';
  case HOLD = 'HOLD';
  case DONE = 'DONE';

  public function label(): string
  {
    return match ($this) {
      self::NEW => 'Nova',
      self::ASSIGNED => 'Atribuida',
      self::IN_PROGRESS => 'Em Andamento',
      self::IN_REVIEW => 'Em Revisao',
      self::HOLD => 'Em Espera',
      self::DONE => 'Concluida',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::NEW => 'badge-warning',
      self::ASSIGNED => 'badge-success',
      self::IN_PROGRESS => 'badge-primary',
      self::IN_REVIEW => 'badge-info',
      self::HOLD => 'badge-warning',
      self::DONE => 'badge-secondary',
    };
  }
}
```

### Mail: NewComment
```php
class NewComment extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(
    public User $recipient,
    public User $actor,
    public Comment $comment,
    public Model $commentable
  ) {}

  public function build(): self
  {
    return $this->subject('Novo comentario registrado')
      ->view('emails.comment.new-comment');
  }
}
```

## Dump do banco (database/schema/mysql-schema.sql)
```sql
/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `commentable_type` varchar(255) NOT NULL,
  `commentable_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `text` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_user_id_foreign` (`user_id`),
  KEY `comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  KEY `comments_parent_id_foreign` (`parent_id`),
  CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `discussable_type` varchar(255) NOT NULL,
  `discussable_id` bigint(20) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_items_meeting_id_discussable_type_discussable_id_unique` (`meeting_id`,`discussable_type`,`discussable_id`),
  KEY `meeting_items_discussable_type_discussable_id_index` (`discussable_type`,`discussable_id`),
  KEY `meeting_items_meeting_id_order_index` (`meeting_id`,`order`),
  CONSTRAINT `meeting_items_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_projects_meeting_id_project_id_unique` (`meeting_id`,`project_id`),
  KEY `meeting_projects_project_id_foreign` (`project_id`),
  CONSTRAINT `meeting_projects_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_projects_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meetings_created_by_foreign` (`created_by`),
  KEY `meetings_updated_by_foreign` (`updated_by`),
  KEY `meetings_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `meetings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `meetings_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `meetings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `phases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `phases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_initial` tinyint(1) NOT NULL DEFAULT 0,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phases_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `module_id` bigint(20) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_modules_project_id_module_id_unique` (`project_id`,`module_id`),
  KEY `project_modules_module_id_foreign` (`module_id`),
  CONSTRAINT `project_modules_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_modules_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_type_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_type_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_type_id` bigint(20) unsigned NOT NULL,
  `module_id` bigint(20) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `editable` tinyint(1) NOT NULL DEFAULT 1,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_type_modules_project_type_id_module_id_unique` (`project_type_id`,`module_id`),
  KEY `project_type_modules_module_id_foreign` (`module_id`),
  KEY `project_type_modules_project_type_id_index` (`project_type_id`),
  CONSTRAINT `project_type_modules_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_type_modules_project_type_id_foreign` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_type_phases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_type_phases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_type_id` bigint(20) unsigned NOT NULL,
  `phase_id` bigint(20) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_initial` tinyint(1) NOT NULL DEFAULT 0,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_type_phases_project_type_id_phase_id_unique` (`project_type_id`,`phase_id`),
  KEY `project_type_phases_phase_id_foreign` (`phase_id`),
  KEY `project_type_phases_project_type_id_order_index` (`project_type_id`,`order`),
  CONSTRAINT `project_type_phases_phase_id_foreign` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_type_phases_project_type_id_foreign` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'CONTRIBUTOR',
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_user_project_id_user_id_unique` (`project_id`,`user_id`),
  KEY `project_user_user_id_foreign` (`user_id`),
  CONSTRAINT `project_user_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `project_type_id` bigint(20) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `visibility` varchar(255) NOT NULL DEFAULT 'PRIVATE',
  `permission_inheritance` varchar(255) NOT NULL DEFAULT 'FULL',
  `phase_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_slug_unique` (`slug`),
  KEY `projects_created_by_foreign` (`created_by`),
  KEY `projects_updated_by_foreign` (`updated_by`),
  KEY `projects_deleted_by_foreign` (`deleted_by`),
  KEY `projects_project_type_id_foreign` (`project_type_id`),
  KEY `projects_parent_id_foreign` (`parent_id`),
  KEY `projects_phase_id_foreign` (`phase_id`),
  CONSTRAINT `projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `projects_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `projects_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_phase_id_foreign` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_project_type_id_foreign` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`),
  CONSTRAINT `projects_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `tag_id` bigint(20) unsigned NOT NULL,
  `taggable_type` varchar(255) NOT NULL,
  `taggable_id` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `taggables_tag_id_taggable_id_taggable_type_unique` (`tag_id`,`taggable_id`,`taggable_type`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`slug`)),
  `type` varchar(255) DEFAULT NULL,
  `order_column` int(11) DEFAULT NULL,
  `color` varchar(255) NOT NULL DEFAULT 'badge-dark',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_user_task_id_user_id_unique` (`task_id`,`user_id`),
  KEY `task_user_user_id_foreign` (`user_id`),
  CONSTRAINT `task_user_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` tinyint(3) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_via_project` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag para identificar tasks que sofreram soft delete em cascata via Project. Evita restaurar indevidamente tasks que já haviam sido deletadas manualmente antes da exclusão do projeto.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_project_id_foreign` (`project_id`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_updated_by_foreign` (`updated_by`),
  KEY `tasks_deleted_by_foreign` (`deleted_by`),
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tasks_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `codpes` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2022_05_25_113413_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2022_05_25_113413_update_senhaunica_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_03_25_170257_create_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_03_25_172139_create_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_03_25_181000_create_project_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_03_25_181001_create_task_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_22_172311_create_tag_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_24_173347_seed_initial_task_tags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_04_24_174812_seed_initial_project_tags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_05_06_143217_update_member_role_to_contributor_on_project_user_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_05_07_090000_update_task_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_05_11_090000_create_project_types_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_05_11_090100_add_project_type_fields_to_projects_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_05_11_090200_sync_project_status_and_phase',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_05_11_120000_create_modules_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_05_11_120100_create_project_type_modules_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_05_11_120200_create_project_modules_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_05_12_150000_seed_modules_and_project_type_modules',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_12_151000_seed_project_modules',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_13_000001_add_pinned_to_project_user_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_13_090000_create_comments_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_13_100000_create_meetings_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_13_100100_create_meeting_projects_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_15_090000_create_meeting_items_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_21_163044_create_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_21_163045_add_event_column_to_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_05_21_163046_add_batch_uuid_column_to_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_05_22_090000_create_phases_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_05_22_090100_create_project_type_phases_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_05_22_090200_seed_phase_module_and_phases',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_05_22_090300_add_phase_id_to_projects_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_05_26_085220_update_existing_task_statuses',3);
```
