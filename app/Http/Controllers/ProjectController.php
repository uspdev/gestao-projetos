<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\UpdateProjectStatusRequest;
use App\Models\Module;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
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

        Gate::authorize('viewAny', [Project::class, $user]);

        $projects = Project::accessibleBy($user)
            ->with([
                'users',
            ])
            ->withCount('tasks')
            ->latest()
            ->get();

        // O apontamento da view mudou para o diretório padrão de projetos
        return view('projects.index', compact('projects', 'user'));
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

        return redirect()->back()
            ->with('alert-success', 'Projeto criado com sucesso!');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $showDone = request()->boolean('show_done');
        $tasksEnabled = $project->isModuleEnabled('tasks');
        // Carrega os módulos resolvidos para o projeto,
        // garantindo que as configurações específicas do projeto sejam consideradas
        $resolvedModules = Module::resolveForProject($project);
        $project = $project->load([
            'users',
            'tags',
            // Filtra as tarefas para retornar apenas aquelas cujo projeto
            // tem o módulo de tarefas habilitado, e considerando o filtro de mostrar ou não as tarefas concluídas
            'projectType',
            'tasks' => fn($query) => $query
                ->when(! $tasksEnabled, fn($query) => $query->whereRaw('1 = 0'))
                ->when($tasksEnabled && ! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
                ->when($tasksEnabled, fn($query) => $query->with('tags')),
        ]);

        return view('projects.show', compact('project', 'resolvedModules'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            if (array_key_exists('description', $data) && is_string($data['description'])) {
                $data['description'] = html_entity_decode($data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $project->update($data);

            $project->syncTagsByIds($request->tags ?? []);
        });

        return redirect()->route('projects.show', $project)
            ->with('alert-success', 'Projeto atualizado com sucesso!');
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

    public function settings(Project $project)
    {
        Gate::authorize('view', $project);

        $resolvedModules = Module::resolveForProject($project);

        return view('projects.settings', compact('project', 'resolvedModules'));
    }
}
