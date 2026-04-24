<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function create()
    {
        Gate::authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $project = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ProjectStatus::PLANNING->value;

            $project = Project::create($data);
            $project->users()->attach(Auth::id(), ['role' => ProjectUserRole::OWNER->value]);

            return $project;
        });

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projeto criado com sucesso!');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);
        $project = $project->load([
            'users:id,name,email',
            'tasks:id,project_id,title,priority,status,start_date,due_date',
            'tasks.tags',
        ]);

        $availableTags = Tag::withType('tasks')
            ->select('id', 'name', 'color', 'description')
            ->orderBy('name')
            ->get();

        return view('projects.show', compact('project', 'availableTags'));
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return view('projects.partials.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projeto atualizado com sucesso!');
    }

    public function updateProjectStatus(UpdateProjectStatusRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->route('projects.show', $project)
            ->with('success', 'Status do projeto atualizado com sucesso!');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);
        DB::transaction(function () use ($project) {
            $project->delete();
        });

        return redirect()->route('users.projects.index', Auth::id())
            ->with('success', 'Projeto excluido com sucesso!');
    }

    public function storeMember(StoreProjectMemberRequest $request, Project $project)
    {
        $data = $request->validated();

        DB::transaction(function () use ($project, $data) {
            $project->users()->syncWithoutDetaching([$data['user_id'] => [
                'role' => $data['role'],
            ]]);
        });

        return redirect()->route('projects.show', $project)
            ->with('success', 'Membro adicionado ao projeto com sucesso!');
    }

    public function updateMemberRole(UpdateProjectMemberRoleRequest $request, Project $project, User $user)
    {
        abort_unless($user->isMemberOfProject($project), 404);

        $data = $request->validated();
        $newRole = ProjectUserRole::from($data['role']);

        if ($project->isLastOwner($user) && $newRole !== ProjectUserRole::OWNER) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'O último owner do projeto não pode ter sua role alterada.');
        }

        DB::transaction(function () use ($project, $user, $newRole) {
            $project->users()->updateExistingPivot($user->id, [
                'role' => $newRole->value,
            ]);
        });

        return redirect()->route('projects.show', $project);
    }

    public function selectableMembers(Project $project)
    {
        Gate::authorize('storeMember', $project);

        $users = User::selectableToProject($project->id)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function destroyMember(Project $project, User $user)
    {
        Gate::authorize('storeMember', $project);

        $destroyedIsOwner = $project->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', ProjectUserRole::OWNER->value)
            ->exists();

        if ($destroyedIsOwner) {
            $ownersCount = $project->users()
                ->wherePivot('role', ProjectUserRole::OWNER->value)
                ->count();

            if ($ownersCount <= 1) {
                return redirect()->route('projects.show', $project)
                    ->with('error', 'O projeto precisa ter pelo menos um owner.');
            }
        }

        DB::transaction(function () use ($project, $user) {
            $project->users()->detach($user->id);
        });

        return redirect()->route('projects.show', $project)
            ->with('success', 'Membro removido do projeto com sucesso!');
    }
}
