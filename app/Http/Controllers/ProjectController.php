<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function create()
    {
        Gate::authorize('create', Project::class);

        return view('Project.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $project = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ProjectStatus::PLANNING->value;

            $project = Project::create($data);
            $project->users()->attach(Auth::id());

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
            'tasks:id,project_id,title,status,due_date',
        ]);

        return view('Project.show', compact('project'));
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return view('Project.update', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project = DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto atualizado com sucesso!');
    }
    
    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);
        DB::transaction(function () use ($project) {
            $project->delete();
        });

        return redirect()->route('projects.index')
                         ->with('success', 'Projeto excluido com sucesso!');
    }
}
