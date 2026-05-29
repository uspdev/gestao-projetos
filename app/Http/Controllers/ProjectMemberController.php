<?php

namespace App\Http\Controllers;

use App\Enums\Project\ProjectUserRole;
use App\Http\Requests\Project\StoreProjectMemberRequest;
use App\Http\Requests\Project\UpdateProjectMemberRoleRequest;
use App\Mail\ProjectUserAdded;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Uspdev\Replicado\Pessoa;

class ProjectMemberController extends Controller
{
    /**
     * Adiciona um membro a um projeto.
     */
    public function store(StoreProjectMemberRequest $request, Project $project)
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
        // Lida com a notificação de adição ao projeto após a transaction
        // para evitar enviar emails caso haja falha na adição do membro ao projeto
        $actor = Auth::user();
        if ($actor && $actor->id !== $user->id) {
            Mail::to($user->email)->queue(new ProjectUserAdded($user, $actor, $project));
        }

        return redirect()->back()
            ->with('alert-success', 'Membro adicionado ao projeto com sucesso!');
    }

    /**
     * Atualiza a role de um membro num projeto.
     */
    public function updateRole(UpdateProjectMemberRoleRequest $request, Project $project, User $user)
    {
        abort_unless($user->isMemberOfProject($project), 404);

        $data = $request->validated();
        $newRole = ProjectUserRole::from($data['role']);

        if ($project->isLastAdmin($user) && $newRole !== ProjectUserRole::ADMIN) {
            return redirect()->route('projects.settings', $project)
                ->with('alert-danger', 'O último admin do projeto não pode ter sua role alterada.');
        }
        if ($project->isAdminInParent($user)) {
            // um admin do pai precisa ter privilegio de admin nos filhos
            if ($newRole !== ProjectUserRole::ADMIN) {
                return redirect()->route('projects.settings', $project)
                    ->with('alert-danger', 'Um admin do projeto pai precisa ter privilégio de admin neste projeto.');
            }
        }

        DB::transaction(function () use ($project, $user, $newRole) {
            $project->users()->updateExistingPivot($user->id, [
                'role' => $newRole->value,
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Função do membro atualizada com sucesso!');
    }

    /**
     * Remove um membro de um projeto.
     */
    public function destroy(Project $project, User $user)
    {
        Gate::authorize('storeMember', $project);

        if ($user->isAdminOfProject($project) && $project->isLastAdmin($user)) {
            return redirect()->route('projects.show', $project)
                ->with('alert-danger', 'O projeto precisa ter pelo menos um admin.');
        }

        DB::transaction(function () use ($project, $user) {
            $project->users()->detach($user->id);
        });

        return redirect()->back()
            ->with('alert-success', 'Membro removido do projeto com sucesso!');
    }

    /**
     * Retorna usuários selecionáveis para adicionar como membros de um projeto..
     */
    public function selectable(Request $request, Project $project)
    {
        Gate::authorize('storeMember', $project);

        $term = trim((string) $request->input('term', ''));

        //todo: se não houver replicado precisa buscar na base local
        if ($term === '' || !function_exists('hasReplicado') || !hasReplicado()) {
            return response()->json(['results' => []]);
        }

        $excludedCodpes = $project->users()
            ->whereNotNull('users.codpes')
            ->pluck('users.codpes')
            ->map(fn($codpes) => (string) $codpes)
            ->all();

        $results = [];


        //todo: poderia ter um método no replicado procurarPorNomeouCodpes. Poderá ser usado no senhaunica-socialite também
        // este método todo poderia ser usado de lá. é necessário ajustar o findUsersGate no config.
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
            }

            if (!empty($results)) {
                return response()->json(['results' => $results]);
            }
        }

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
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Permite que um usuário com permissão herdada ingresse ativamente no subprojeto.
     */
    public function joinInherited(Request $request, Project $project)
    {
        $user = Auth::user();
        if ($user->isMemberOfProject($project)) {
            return redirect()->back()
                ->with('alert-info', 'Você já é um membro ativo deste projeto.');
        }

        $inheritedRole = $user->getInheritedRoleFor($project);
        if (!$inheritedRole) {
            abort(403, 'Você não possui permissões herdadas elegíveis para ingressar neste projeto.');
        }

        DB::transaction(function () use ($project, $user, $inheritedRole) {
            $project->users()->syncWithoutDetaching([$user->id => [
                'role' => $inheritedRole->value,
            ]]);
        });

        return redirect()->back()
            ->with('alert-success', "Você ingressou no projeto como {$inheritedRole->label()} com sucesso!");
    }
}
