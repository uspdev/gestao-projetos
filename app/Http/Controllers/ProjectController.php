<?php

namespace App\Http\Controllers;

use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Enums\Task\TaskStatus;
use App\Enums\Watch\WatchEventType;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectDescriptionRequest;
use App\Http\Requests\Project\UpdateProjectModuleRequest;
use App\Http\Requests\Project\UpdateProjectNameRequest;
use App\Http\Requests\Project\UpdateProjectPermissionInheritanceRequest;
use App\Http\Requests\Project\UpdateProjectSlugRequest;
use App\Http\Requests\Project\UpdateProjectStatusRequest;
use App\Http\Requests\Project\UpdateProjectTagsRequest;
use App\Http\Requests\Project\UpdateProjectVisibilityRequest;
use App\Models\Module;
use App\Models\PendingWatchNotification;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectType;
use App\Services\MentionIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct()
    {

        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('projects');

            return $next($request);
        })->only(['index', 'create', 'show']);
    }

    public function index()
    {
        $user = Auth::user();
        Gate::authorize('viewAny', [Project::class, $user]);

        $projects = $user->projects()
            ->with([
                'users' => fn($query) => $query->where('users.id', $user->id),
                'tags',
            ])
            ->orderByActivity()
            ->get();

        if ($projects->isEmpty()) {
            return view('projects.index-no-project');
        }

        return view('projects.index', compact(
            'projects',
            'user',
        ));
    }

    public function show(Project $project, Request $request)
    {
        Gate::authorize('view', $project);

        $view = $request->query('view');

        $showDone = request()->boolean('show_done');
        $tasksEnabled = $project->isModuleEnabled('tasks');
        // Carrega os módulos resolvidos para o projeto,
        // garantindo que as configurações específicas do projeto sejam consideradas
        $project = $project->load([
            'users',
            'tags',
            'parent',
            'projectType',
            'projectType.phases',
            'phase',
            'tasks' => fn($query) => $query
                ->when(! $tasksEnabled, fn($query) => $query->whereRaw('1 = 0'))
                ->when($tasksEnabled && ! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
                ->when($tasksEnabled, fn($query) => $query->with('tags')),
        ]);

        if ($view == 'subprojects' && $project->children->isNotEmpty()) {
            return view('projects.subprojects', compact('project'));
        }

        $files = $project->media()
            ->with('uploader')
            ->latest()
            ->paginate(20, ['*'], 'files_page');

        return view('projects.show', compact(
            'project',
            'files',
        ));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Project::class);
        $parentProject = $this->resolveParentForCreation($request);
        // Permite receber um parâmetro opcional de tipo de projeto (id ou slug) para direcionar
        // o usuário diretamente para o formulário de criação específico daquele tipo,
        // ou para exibir a tela de seleção de tipo caso o parâmetro não seja fornecido
        $projectTypeParam = trim((string) $request->input('project_type', ''));

        // Se um parâmetro de tipo de projeto for fornecido, tenta localizar o tipo correspondente por id ou slug,
        // e exibe o formulário de criação específico para aquele tipo, passando os módulos relacionados
        if ($projectTypeParam !== '') {
            $projectTypeQuery = ProjectType::query()->with([
                'modules' => fn($query) => $query->orderBy('name'),
                'phases'  => fn($query)  => $query->where('phases.is_active', true),
            ])->where('enabled', true);

            if ($parentProject) {
                $projectTypeQuery->where('slug', '!=', Project::ORGANIZATIONAL_TYPE_SLUG);
            }

            if (ctype_digit($projectTypeParam)) {
                $projectTypeQuery->where('id', (int) $projectTypeParam);
            } else {
                $projectTypeQuery->where('slug', $projectTypeParam);
            }

            $projectType = $projectTypeQuery->firstOrFail();

            return view('projects.create-form', compact('projectType', 'parentProject'));
        }

        // Se nenhum parâmetro de tipo de projeto for fornecido, exibe a tela de seleção de tipo,
        // listando todos os tipos de projeto não organizacional disponíveis
        // ordenados por nome, e seus módulos
        $projectTypes = ProjectType::query()
            ->with(['modules' => fn($query) => $query->orderBy('name')])
            ->where('enabled', true)
            ->where('slug', '!=', 'organizacional')
            ->orderBy('name')->get();

        $organizacional = ProjectType::query()
            ->where(['slug' => 'organizacional'])
            ->first();

        return view('projects.create', compact('projectTypes', 'organizacional', 'parentProject'));
    }

    /**
     * Resolve o projeto pai quando a criação foi iniciada pelo cartão de subprojetos.
     */
    protected function resolveParentForCreation(Request $request): ?Project
    {
        $parentId = trim((string) $request->query('parent_id', ''));

        if ($parentId === '') {
            return null;
        }

        abort_unless(ctype_digit($parentId) && (int) $parentId > 0, 404);

        $parentProject = Project::query()
            ->with('projectType')
            ->findOrFail((int) $parentId);

        Gate::authorize('storeMember', $parentProject);

        abort_unless(
            $parentProject->isRootProject() && $parentProject->isOrganizational(),
            404
        );

        return $parentProject;
    }

    /**
     * Alterna o status de fixação (pinned) de um projeto
     */
    public function togglePin(Project $project)
    {
        Gate::authorize('view', $project);

        $user = Auth::user();
        $membership = $project->users()->where('users.id', $user->id)->first();
        abort_unless($membership, 403);

        $isPinned = (bool) ($membership->pivot->pinned ?? false);

        $project->users()->updateExistingPivot($user->id, [
            'pinned' => ! $isPinned,
        ]);

        return redirect()->back()
            ->with('alert-success', $isPinned
                ? 'Projeto removido dos pinados com sucesso!'
                : 'Projeto fixado com sucesso!');
    }

    public function store(StoreProjectRequest $request, MentionIndexer $mentionIndexer)
    {
        $project = DB::transaction(function () use ($request, $mentionIndexer) {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ProjectStatus::DRAFT->value;

            $project = Project::create($data);
            $project->users()->attach(Auth::id(), ['role' => ProjectUserRole::ADMIN->value]);

            $mentionIndexer->validateAllMentions($project, 'description', $data['description'] ?? null);
            $mentionIndexer->synchronize($project, 'description', $data['description'] ?? null, Auth::id());

            $project->syncTagsByIds($request->tags ?? null);

            return $project;
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('alert-success', 'Projeto criado com sucesso!');
    }

    /**
     * Retorna projetos elegiveis para vincular como subprojeto.
     *
     * O endpoint de busca para vincular subprojetos precisa retornar projetos raiz,
     *  sem subprojetos, e que compartilhem pelo menos um admin com o projeto pai,
     * garantindo que o usuário tenha permissão de update nesses projetos candidatos
     */
    public function selectableSubprojects(Request $request, Project $project)
    {
        Gate::authorize('storeMember', $project);

        if ($project->isSubproject() || ! $project->isOrganizational()) {
            return response()->json(['results' => []]);
        }

        $term = trim((string) $request->input('term', ''));
        $selectedId = $request->input('id');
        if ($term === '' && ! $selectedId) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $isGlobalAdmin = $user?->isAdmin() ?? false;

        // A consulta busca projetos raiz, excluindo o projeto atual, sem subprojetos, e com pelo menos um admin em comum,
        // e carrega os tipos de projeto e os admins para exibição nos resultados
        $query = Project::accessibleBy($user)
            ->whereNull('parent_id')
            ->whereKeyNot($project->getKey())
            // Exclui projetos do tipo organizacional da lista de candidatos,
            // pois não é permitido vincular projetos organizacionais como subprojetos
            ->whereHas('projectType', function ($q) {
                $q->where('slug', '!=', Project::ORGANIZATIONAL_TYPE_SLUG);
            })
            ->doesntHave('children')
            ->whereHas('users', function ($q) use ($user, $isGlobalAdmin) {
                if (! $isGlobalAdmin) {
                    $q->where('users.id', $user->id);
                }

                $q->wherePivot('role', ProjectUserRole::ADMIN->value);
            })
            ->with([
                'projectType',
                'users' => function ($q) use ($user, $isGlobalAdmin) {
                    if (! $isGlobalAdmin) {
                        $q->where('users.id', $user->id);
                    }

                    $q->wherePivot('role', ProjectUserRole::ADMIN->value);
                },
            ])
            ->orderBy('name');

        if ($selectedId) {
            $query->whereKey($selectedId);
        } else {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $projects = $query->take(50)->get();
        // Tratativa para exibir o nome do admin (ou N/A) e o tipo de projeto nos resultados,
        // para auxiliar na identificação dos projetos candidatos
        $results = $projects->map(function (Project $candidate) {
            $adminName = $candidate->users->first()?->name ?? 'N/A';

            return [
                'id' => $candidate->id,
                'text' => $candidate->name,
                'status' => $candidate->status?->label(),
                'admin' => $adminName,
                'type' => $candidate->projectType?->name,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Vincula um projeto existente como subprojeto.
     */
    public function linkSubproject(Request $request, Project $project)
    {
        Gate::authorize('storeMember', $project);

        if ($project->isSubproject()) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'Não é possivel vincular subprojetos a um subprojeto.']);
        }

        if (! $project->isOrganizational()) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'Apenas projetos do tipo organizacional podem vincular subprojetos.']);
        }

        $data = $request->validate([
            'subproject_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $subproject = Project::query()->with(['users', 'children', 'projectType'])->findOrFail($data['subproject_id']);

        Gate::authorize('update', $subproject);

        $blockReason = $subproject->subprojectLinkBlockReason($project);
        if ($blockReason) {
            return redirect()->back()
                ->withErrors(['subproject_id' => $blockReason]);
        }

        // Se passar pelas validações, vincula o subprojeto ao projeto pai atualizando o parent_id do subprojeto
        $subproject->update([
            'parent_id'  => $project->id,
            'updated_by' => Auth::id(),
        ]);

        if ($actor = Auth::user()) {
            PendingWatchNotification::addForWatchers(
                $subproject,
                WatchEventType::SUBPROJECT_LINKED,
                $actor,
                "Vinculado como subprojeto de \"{$project->name}\".",
                null,
                $subproject->watchUrl(),
            );
        }

        return redirect()->back()
            ->with('alert-success', 'Subprojeto vinculado com sucesso!');
    }

    /**
     * Vincula o projeto atual como subprojeto de um projeto pai existente.
     */
    public function linkParent(Request $request, Project $project)
    {
        Gate::authorize('storeMember', $project);

        if ($project->isSubproject()) {
            return redirect()->back()
                ->withErrors(['parent_id' => 'Não é possivel vincular um projeto que já é subprojeto.']);
        }

        if ($project->isOrganizational()) {
            return redirect()->back()
                ->withErrors(['parent_id' => 'Apenas projetos independentes podem ser vinculados a um projeto organizacional.']);
        }

        $data = $request->validate([
            'parent_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $parent = Project::query()->with(['users', 'children', 'projectType'])->findOrFail($data['parent_id']);

        $blockReason = $project->subprojectLinkBlockReason($parent);
        if ($blockReason) {
            return redirect()->back()
                ->withErrors(['parent_id' => $blockReason]);
        }

        $project->update([
            'parent_id'  => $parent->id,
            'updated_by' => Auth::id(),
        ]);

        if ($actor = Auth::user()) {
            PendingWatchNotification::addForWatchers(
                $project,
                WatchEventType::SUBPROJECT_LINKED,
                $actor,
                "Vinculado como subprojeto de \"{$parent->name}\".",
                null,
                $project->watchUrl(),
            );
        }

        return redirect()->back()
            ->with('alert-success', 'Projeto vinculado com sucesso!');
    }

    /**
     * Desvincula um subprojeto do projeto atual.
     */
    public function unlinkSubproject(Request $request, Project $project)
    {
        $data = $request->validate([
            'subproject_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $subproject = Project::with(['users', 'children', 'projectType'])->findOrFail($data['subproject_id']);

        abort_unless(
            Gate::allows('storeMember', $project) || Auth::user()?->isAdminOfProject($subproject),
            403
        );

        if ($subproject->parent_id !== $project->id) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'O projeto selecionado não está vinculado como subprojeto deste projeto.']);
        }

        $subproject->update([
            'parent_id'  => null,
            'updated_by' => Auth::id(),
        ]);

        if ($actor = Auth::user()) {
            PendingWatchNotification::addForWatchers(
                $subproject,
                WatchEventType::SUBPROJECT_UNLINKED,
                $actor,
                "Desvinculado do projeto organizacional \"{$project->name}\".",
                null,
                $subproject->watchUrl(),
            );
        }

        return redirect()->back()
            ->with('alert-success', 'Subprojeto desvinculado com sucesso!');
    }

    /**
     * Atualiza o nome de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectNameRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateName(UpdateProjectNameRequest $request, Project $project)
    {
        $project->update([
            'name'       => $request->validated('name'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()
            ->with('alert-success', 'Nome do projeto atualizado com sucesso!');
    }

    /**
     * Atualiza a URL (slug) de um projeto.
     *
     * Ao trocar slug não é possivel retornar back() pois mudou a URL
     *
     * @param  \App\Http\Requests\Project\UpdateProjectSlugRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSlug(UpdateProjectSlugRequest $request, Project $project)
    {
        $project->update([
            'slug'       => $request->validated('slug'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('projects.settings', $project)
            ->with('alert-success', 'URL do projeto atualizada com sucesso!');
    }

    /**
     * Atualiza a descrição de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectDescriptionRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDescription(UpdateProjectDescriptionRequest $request, Project $project, MentionIndexer $mentionIndexer)
    {
        DB::transaction(function () use ($request, $project, $mentionIndexer): void {
            $description = html_entity_decode(
                $request->validated('description'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            $mentionIndexer->validateNewMentions($project, 'description', $description);
            $project->update([
                'description' => $description,
                'updated_by'  => Auth::id(),
            ]);
            $mentionIndexer->synchronize($project, 'description', $description, Auth::id());
        });

        return redirect()->back()
            ->with('alert-success', 'Descrição do projeto atualizada com sucesso!');
    }

    /**
     * Atualiza as tags de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectTagsRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTags(UpdateProjectTagsRequest $request, Project $project)
    {
        $project->syncTagsByIds(
            $request->validated('tags') ?? []
        );

        return redirect()->back()
            ->with('alert-success', 'Tags do projeto atualizado com sucesso!');
    }
    /**
     * Atualiza os módulos de um projeto.
     * O método de atualização de módulos precisa validar se o módulo existe,
     * se é permitido para o tipo de projeto,  e se as regras de obrigatoriedade e editabilidade são respeitadas,
     * antes de atualizar a configuração do módulo para o projeto
     * @param  \App\Http\Requests\Project\UpdateProjectModuleRequest $request
     * @param  \App\Models\Project $project
     * @param  string $module
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateModule(UpdateProjectModuleRequest $request, Project $project, string $module)
    {
        $moduleSlug = strtolower(trim($module));
        if ($moduleSlug === '') {
            abort(404);
        }

        $projectTypeId = (int) ($project->project_type_id ?? 0);
        $typeConfig = null;
        // Valida se o módulo é permitido para o tipo de projeto, e
        // se as regras de obrigatoriedade e editabilidade são respeitadas
        if ($projectTypeId > 0) {
            $typeConfig = $project->projectTypeModuleConfig($moduleSlug);

            if (! $typeConfig) {
                return redirect()->back()
                    ->withErrors(['module' => 'Este modulo nao esta disponivel para o tipo de projeto.']);
            }
        }

        $moduleModel = $typeConfig['module'] ?? Module::query()->where('slug', $moduleSlug)->first();
        if (! $moduleModel) {
            abort(404);
        }

        $enabled = $request->enabled();

        if ($typeConfig && ($typeConfig['required'] ?? false) && ! $enabled) {
            return redirect()->back()
                ->withErrors(['module' => 'Este modulo e obrigatorio e nao pode ser desativado.']);
        }

        if ($typeConfig && ! ($typeConfig['required'] ?? false) && ! ($typeConfig['editable'] ?? true)) {
            return redirect()->back()
                ->withErrors(['module' => 'Este modulo nao permite alteracoes neste projeto.']);
        }
        // Se passar pelas validações, atualiza ou cria a configuração do módulo para o projeto usando updateOrCreate,
        // garantindo que a configuração seja criada caso não exista, ou atualizada caso já exista,
        ProjectModule::query()->updateOrCreate(
            ['project_id' => $project->id, 'module_id' => $moduleModel->id],
            ['enabled' => $enabled]
        );

        return redirect()->back()
            ->with('alert-success', $enabled
                ? 'Modulo ativado com sucesso!'
                : 'Modulo desativado com sucesso!');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);
        DB::transaction(function () use ($project) {
            $project->delete();
        });

        return redirect()->route('projects.index', Auth::id())
            ->with('alert-success', 'Projeto excluido com sucesso!');
    }

    /**
     * Atualiza o status de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectStatusRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProjectStatus(UpdateProjectStatusRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data               = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'Status do projeto atualizado com sucesso!');
    }

    /**
     * Atualiza a visibilidade de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectVisibilityRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProjectVisibility(UpdateProjectVisibilityRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'Visibilidade do projeto atualizada com sucesso!');
    }

    /**
     * Atualiza a heranca de permissoes de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectPermissionInheritanceRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProjectPermissionInheritance(UpdateProjectPermissionInheritanceRequest $request, Project $project)
    {
        $project->update([
            'permission_inheritance' => $request->validated(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()
            ->with('alert-success', 'Heranca de permissoes atualizada com sucesso!');
    }

    /**
     * Visualização de configurações de um projeto.
     *
     */
    public function settings(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(
            'users',
            'projectType.phases',
            'phase',
            'projectType.modules',
        );

        return view('projects.settings', compact('project'));
    }

    /**
     * Lista os membros vinculados diretamente a cada subprojeto de um projeto organizacional.
     */
    public function subprojectMembers(Project $project)
    {
        Gate::authorize('view', $project);
        abort_unless($project->isOrganizational(), 404);

        $project->load([
            'users',
            'projectType.modules',
            'children' => fn($query) => $query->orderBy('name'),
            'children.users',
        ]);

        return view('projects.subproject-members', compact('project'));
    }
}
