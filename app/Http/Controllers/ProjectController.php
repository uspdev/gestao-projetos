<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectDescriptionRequest;
use App\Http\Requests\Project\UpdateProjectNameRequest;
use App\Http\Requests\Project\UpdateProjectSlugRequest;
use App\Http\Requests\Project\UpdateProjectTagsRequest;
use App\Http\Requests\Project\UpdateProjectPhaseRequest;
use App\Http\Requests\Project\UpdateProjectPermissionInheritanceRequest;
use App\Http\Requests\Project\UpdateProjectStatusRequest;
use App\Http\Requests\Project\UpdateProjectVisibilityRequest;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    private const ORGANIZATIONAL_PROJECT_TYPE_SLUG = 'organizacional';

    public function __construct()
    {

        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        })->only(['index', 'create', 'show']);
    }

    public function index()
    {
        $user = Auth::user();
        $search = trim((string) request()->input('search', ''));
        $viewAll = session('admin_view_all', false);

        Gate::authorize('viewAny', [Project::class, $user]);

        // Se o usuário é admin mas não quer ver tudo, mostrar apenas seus projetos
        if ($user->isAdmin() && !$viewAll) {
            $projectsQuery = $user->projects()
                ->with([
                    'users' => fn($query) => $query->where('users.id', $user->id),
                ])
                ->withCount('tasks')
                ->latest();
        } else {
            $projectsQuery = Project::accessibleBy($user)
                ->with([
                    'users' => fn($query) => $query->where('users.id', $user->id),
                ])
                ->withCount('tasks')
                ->latest();
        }

        if ($search !== '') {
            $projectsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $projectsQuery->get();
        $pinnedProjects = $projects->filter(fn(Project $project) => $project->isPinnedBy($user))->values();

        // Para parentProjects também respeitar a preferência de view
        if ($user->isAdmin() && !$viewAll) {
            $parentProjects = $user->projects()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
        } else {
            $parentProjects = Project::accessibleBy($user)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
        }

        // O apontamento da view mudou para o diretório padrão de projetos

        if($projects->isEmpty()) {
            return view('projects.index-no-project');
        }
        return view('projects.index', compact('projects', 'pinnedProjects', 'user', 'search', 'parentProjects', 'viewAll'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Project::class);
        // Permite receber um parâmetro opcional de tipo de projeto (id ou slug) para direcionar
        // o usuário diretamente para o formulário de criação específico daquele tipo,
        // ou para exibir a tela de seleção de tipo caso o parâmetro não seja fornecido
        $projectTypeParam = trim((string) $request->input('project_type', ''));

        // Se um parâmetro de tipo de projeto for fornecido, tenta localizar o tipo correspondente por id ou slug,
        // e exibe o formulário de criação específico para aquele tipo, passando os módulos relacionados
        if ($projectTypeParam !== '') {
            $projectTypeQuery = ProjectType::query()->with(['modules' => fn($query) => $query->orderBy('name')]);

            if (ctype_digit($projectTypeParam)) {
                $projectTypeQuery->where('id', (int) $projectTypeParam);
            } else {
                $projectTypeQuery->where('slug', $projectTypeParam);
            }

            $projectType = $projectTypeQuery->firstOrFail();

            return view('projects.create-form', compact('projectType'));
        }
        // Se nenhum parâmetro de tipo de projeto for fornecido, exibe a tela de seleção de tipo,
        // listando todos os tipos de projeto disponíveis ordenados por nome, e seus módulos
        $projectTypes = ProjectType::query()
            ->with(['modules' => fn($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('projects.create', compact('projectTypes'));
    }

    public function togglePin(Project $project)
    {
        $user = Auth::user();

        Gate::authorize('view', $project);

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

    public function store(StoreProjectRequest $request)
    {
        $project = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ProjectStatus::DRAFT->value;

            $project = Project::create($data);
            $project->users()->attach(Auth::id(), ['role' => ProjectUserRole::ADMIN->value]);

            $project->syncTagsByIds($request->tags ?? null);

            return $project;
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('alert-success', 'Projeto criado com sucesso!');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $user = Auth::user();
        $showDone = request()->boolean('show_done');
        $tasksEnabled = $project->isModuleEnabled('tasks');
        // Carrega os módulos resolvidos para o projeto,
        // garantindo que as configurações específicas do projeto sejam consideradas
        $resolvedModules = Module::resolveForProject($project);
        $project = $project->load([
            'users',
            'tags',
            'parent',
            'projectType',
            'tasks' => fn($query) => $query
                ->when(! $tasksEnabled, fn($query) => $query->whereRaw('1 = 0'))
                ->when($tasksEnabled && ! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
                ->when($tasksEnabled, fn($query) => $query->with('tags')),
        ]);

        $subprojects = collect();
        $contextParentProject = null;
        $linkableSubprojects = collect();

        if (! $project->isSubproject() && $this->isOrganizationalProject($project)) {
            // Para projetos raiz, carrega os subprojetos diretamente relacionados para exibição
            $contextParentProject = $project;
            $subprojects = $project->children()
                ->with(['tags', 'projectType'])
                ->withCount(['tasks', 'users'])
                ->orderBy('name')
                ->get();
            // E também carrega os projetos elegíveis para vincular como subprojetos,
            // que são projetos raiz sem subprojetos e com pelo menos um admin em comum
            $linkableSubprojects = Project::query()
                ->whereNull('parent_id')
                ->whereKeyNot($project->getKey())
                ->whereHas('projectType', function ($q) {
                    $q->where('slug', '!=', self::ORGANIZATIONAL_PROJECT_TYPE_SLUG);
                })
                // children gera uma subconsulta para verificar a existência de subprojetos
                ->doesntHave('children')
                // A verificação de admin em comum é feita com whereHas na relação de usuários,
                // filtrando por projetos que compartilhem pelo menos um admin com o projeto atual
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id)
                        ->where('project_user.role', ProjectUserRole::ADMIN->value);
                })
                // Carrega os tipos de projeto e os admins para exibição nos resultados, facilitando a identificação dos projetos candidatos
                ->with([
                    'projectType',
                    'users' => function ($q) {
                        $q->where('project_user.role', ProjectUserRole::ADMIN->value)
                            ->orderBy('project_user.created_at');
                    },
                ])
                ->orderBy('name')
                ->get();
        }
        // Para subprojetos, carrega os projetos irmãos (mesmo parent_id) para exibição e possível navegação
        $parentProjects = Project::accessibleBy($user)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('projects.show', compact(
            'project',
            'resolvedModules',
            'subprojects',
            'parentProjects',
            'contextParentProject',
            'linkableSubprojects'
        ));
    }

    /**
     * Retorna projetos elegiveis para vincular como subprojeto.
     */
    public function selectableSubprojects(Request $request, Project $project)
    {
        Gate::authorize('storeMember', $project);

        if ($project->isSubproject() || ! $this->isOrganizationalProject($project)) {
            return response()->json(['results' => []]);
        }
        // O endpoint de busca para vincular subprojetos precisa retornar projetos raiz,
        // sem subprojetos, e que compartilhem pelo menos um admin com o projeto pai,
        // garantindo que o usuário tenha permissão de update nesses projetos candidatos
        $term = trim((string) $request->input('term', ''));
        $selectedId = $request->input('id');
        if ($term === '' && ! $selectedId) {
            return response()->json(['results' => []]);
        }

        $adminIds = $project->adminIds();
        if ($adminIds->isEmpty()) {
            return response()->json(['results' => []]);
        }
        // A consulta busca projetos raiz, excluindo o projeto atual, sem subprojetos, e com pelo menos um admin em comum,
        // e carrega os tipos de projeto e os admins para exibição nos resultados
        $query = Project::accessibleBy($request->user())
            ->whereNull('parent_id')
            ->whereKeyNot($project->getKey())
            // Exclui projetos do tipo organizacional da lista de candidatos,
            // pois não é permitido vincular projetos organizacionais como subprojetos
            ->whereHas('projectType', function ($q) {
                $q->where('slug', '!=', self::ORGANIZATIONAL_PROJECT_TYPE_SLUG);
            })
            ->doesntHave('children')
            ->whereHas('users', function ($q) use ($adminIds) {
                $q->whereIn('users.id', $adminIds)
                    ->wherePivot('role', ProjectUserRole::ADMIN->value);
            })
            ->with([
                'projectType',
                'users' => function ($q) use ($adminIds) {
                    $q->whereIn('users.id', $adminIds)
                        ->wherePivot('role', ProjectUserRole::ADMIN->value);
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

        if (! $this->isOrganizationalProject($project)) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'Apenas projetos do tipo organizacional podem vincular subprojetos.']);
        }

        $data = $request->validate([
            'subproject_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $subproject = Project::with(['users', 'children'])->findOrFail($data['subproject_id']);

        Gate::authorize('update', $subproject);

        // ========Validações======
        if ($subproject->getKey() === $project->getKey()) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'Selecione um projeto diferente do projeto atual.']);
        }

        if (! $subproject->isRootProject()) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'O projeto selecionado ja e um subprojeto.']);
        }

        if ($this->isOrganizationalProject($project) && $this->isOrganizationalProject($subproject)) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'Projetos organizacionais não podem ser subprojetos de outros projetos organizacionais.']);
        }

        if ($subproject->hasSubprojects()) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'O projeto selecionado possui subprojetos e nao pode ser vinculado.']);
        }

        if (! $project->isRootProject()) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'O projeto pai nao pode ser um subprojeto.']);
        }

        if (! $subproject->sharesAnyAdmin($project)) {
            return redirect()->back()
                ->withErrors(['subproject_id' => 'O projeto selecionado possui administrador diferente do projeto pai.']);
        }

        // Se passar pelas validações, vincula o subprojeto ao projeto pai atualizando o parent_id do subprojeto
        DB::transaction(function () use ($subproject, $project) {
            $subproject->update([
                'parent_id' => $project->id,
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()->route('projects.show', $project)
            ->with('alert-success', 'Subprojeto vinculado com sucesso!');
    }

    private function isOrganizationalProject(Project $project): bool
    {
        $project->loadMissing('projectType:id,slug');

        return $project->projectType?->slug === self::ORGANIZATIONAL_PROJECT_TYPE_SLUG;
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
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'Nome do projeto atualizado com sucesso!');
    }

    /**
     * Atualiza a URL (slug) de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectSlugRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSlug(UpdateProjectSlugRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'URL do projeto atualizada com sucesso!');
    }

    /**
     * Atualiza a descrição de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectDescriptionRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDescription(UpdateProjectDescriptionRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            if (array_key_exists('description', $data) && is_string($data['description'])) {
                $data['description'] = html_entity_decode($data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $project->update($data);
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
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();

            $project->syncTagsByIds($data['tags'] ?? []);
        });

        return redirect()->back()
            ->with('alert-success', 'Tags do projeto atualizado com sucesso!');
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
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'Status do projeto atualizado com sucesso!');
    }

    /**
     * Atualiza a fase de um projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectPhaseRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProjectPhase(UpdateProjectPhaseRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'Fase do projeto atualizada com sucesso!');
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
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->with('alert-success', 'Heranca de permissoes atualizada com sucesso!');
    }

    public function settings(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load('projectType');
        $resolvedModules = Module::resolveForProject($project);

        return view('projects.settings', compact('project', 'resolvedModules'));
    }
}
