<?php

namespace App\Http\Controllers;

use App\Actions\Project\IndexProjectAction;
use App\Actions\Project\ShowProjectAction;
use App\Actions\Project\DestroyProjectAction;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\UpdateProjectAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request, IndexProjectAction $action)
    {
        Gate::authorize('viewAny', Project::class);
        $projects = $action->execute($request->user());

        return view('Project.index', compact('projects'));
    }

    public function create()
    {
        Gate::authorize('create', Project::class);

        return view('Project.create');
    }

    public function store(StoreProjectRequest $request, CreateProjectAction $action)
    {
        $project = $action->execute($request->validated(), $request->user()); //validacao faz sentido?

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto criado com sucesso!');
    }

    public function show(Project $project, ShowProjectAction $action)
    {
        Gate::authorize('view', $project);
        $project = $action->execute($project);

        return view('Project.show', compact('project'));
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return view('Project.update', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectAction $action)
    {
        $project = $action->execute($project, $request->validated(), Auth::id());

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto atualizado com sucesso!');
    }
    
    public function destroy(Project $project, DestroyProjectAction $action)
    {
        Gate::authorize('delete', $project);
        $action->execute($project);

        return redirect()->route('projects.index')
                         ->with('success', 'Projeto excluido com sucesso!');
    }
}
