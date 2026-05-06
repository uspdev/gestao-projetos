<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\StoreProjectMemberRequest;
use App\Http\Requests\Project\UpdateProjectMemberRoleRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\UpdateProjectStatusRequest;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Uspdev\Replicado\Pessoa;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        });
    }

    public function store(StoreProjectRequest $request)
    {
        $project = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ProjectStatus::PLANNING->value;

            $project = Project::create($data);
            $project->users()->attach(Auth::id(), ['role' => ProjectUserRole::OWNER->value]);

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
        $project = $project->load([
            'users',
            'tags',
            'tasks' => fn ($query) => $query
                ->when(! $showDone, fn ($query) => $query->where('status', '!=', TaskStatus::DONE->value))
                ->with('tags'),
        ]);

        $selectableProjectTags = Tag::forProjects();
        $selectableTaskTags = Tag::forTasks();

        $selectedTasksTagsIds = $project->tasks->mapWithKeys(function ($task) {
            return [$task->id => $task->tagsWithType('tasks')->pluck('id')->all()];
        });

        return view('projects.show', compact(
            'project',
            'selectableProjectTags',
            'selectableTaskTags',
            'selectedTasksTagsIds'
        ));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

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

    /**
     * Adiciona um membro a um projeto.
     *
     * @param  \App\Http\Requests\Project\StoreProjectMemberRequest  $request
     * @param  \App\Models\Project  $project
     */
    public function storeMember(StoreProjectMemberRequest $request, Project $project)
    {
        $data = $request->validated();
        $user = User::findOrCreateFromReplicado($data['codpes']);

        if (!($user instanceof User)) {
            return redirect()->back()
                ->withErrors(['codpes' => $user])
                ->withInput();
        }

        if ($user->isMemberOfProject($project)) {
            return redirect()->back()
                ->withErrors(['codpes' => 'O usuário selecionado já faz parte do projeto.'])
                ->withInput();
        }

        DB::transaction(function () use ($project, $user, $data) {
            $project->users()->syncWithoutDetaching([$user->id => [
                'role' => $data['role'],
            ]]);
        });

        return redirect()->back()
            ->with('alert-success', 'Membro adicionado ao projeto com sucesso!');
    }

    /**
     * Atualiza a role de um membro num projeto.
     *
     * @param  \App\Http\Requests\Project\UpdateProjectMemberRoleRequest  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $user
     */
    public function updateMemberRole(UpdateProjectMemberRoleRequest $request, Project $project, User $user)
    {
        abort_unless($user->isMemberOfProject($project), 404);

        $data = $request->validated();
        $newRole = ProjectUserRole::from($data['role']);

        if ($project->isLastOwner($user) && $newRole !== ProjectUserRole::OWNER) {
            return redirect()->route('projects.show', $project)
                ->with('alert-danger', 'O último dono do projeto não pode ter sua role alterada.');
        }

        DB::transaction(function () use ($project, $user, $newRole) {
            $project->users()->updateExistingPivot($user->id, [
                'role' => $newRole->value,
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Role do membro atualizada com sucesso!');
    }

    /**
     * Retorna usuários selecionáveis para adicionar como membros de um projeto.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectableMembers(Request $request, Project $project)
    {
        Gate::authorize('storeMember', $project);

        $term = trim((string) $request->input('term', ''));

        if ($term === '' || !function_exists('hasReplicado') || !hasReplicado()) {
            return response()->json(['results' => []]);
        }

        $excludedCodpes = $project->users()
            ->whereNotNull('users.codpes')
            ->pluck('users.codpes')
            ->map(fn($codpes) => (string) $codpes)
            ->all();

        $results = [];

        // Se for numérico com 4+ dígitos, tenta buscar por codpes
        if (is_numeric($term) && strlen($term) >= 4) {
            try {
                $pessoa = Pessoa::dump((int) $term);

                if ($pessoa && !in_array((string) $pessoa['codpes'], $excludedCodpes, true)) {
                    $results[] = [
                        'id' => $pessoa['codpes'],
                        'text' => $pessoa['codpes'] . ' ' . $pessoa['nompesttd'],
                    ];
                }
            } catch (\Exception $e) {
                // Se falhar a busca por codpes, continua para busca por nome
            }

            // Se encontrou por codpes, retorna
            if (!empty($results)) {
                return response()->json(['results' => $results]);
            }
        }

        // Busca por nome (texto)
        try {
            $pessoas = Pessoa::procurarPorNome($term) ?? [];
            $pessoas = collect($pessoas)
                ->unique('codpes')
                ->sortBy('nompesttd')
                ->take(50);

            foreach ($pessoas as $pessoa) {
                if (in_array((string) $pessoa['codpes'], $excludedCodpes, true)) {
                    continue;
                }

                $results[] = [
                    'id' => $pessoa['codpes'],
                    'text' => $pessoa['codpes'] . ' ' . $pessoa['nompesttd'],
                ];
            }
        } catch (\Exception $e) {
            // Se falhar a busca por nome, retorna vazio
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Remove um membro de um projeto.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $user
     */
    public function destroyMember(Project $project, User $user)
    {
        Gate::authorize('storeMember', $project);

        if ($user->isOwnerOfProject($project) && $project->isLastOwner($user)) {
            return redirect()->route('projects.show', $project)
                ->with('alert-danger', 'O projeto precisa ter pelo menos um dono.');
        }

        DB::transaction(function () use ($project, $user) {
            $project->users()->detach($user->id);
        });

        return redirect()->back()
            ->with('alert-success', 'Membro removido do projeto com sucesso!');
    }
}
