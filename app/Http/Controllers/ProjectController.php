<?php

namespace App\Http\Controllers;

use App\Actions\Project\IndexProjectAction;
use App\Actions\Project\ShowProjectAction;
use App\Actions\Project\DestroyProjectAction;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\UpdateProjectAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request, IndexProjectAction $action)
    {
        $projects = $action->execute($request->user());

        return view('Project.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', Project::class); // alterar para Gate::authorize

        return view('Project.create');
    }

    public function store(Request $request, CreateProjectAction $action)
    {
        $project = $action->execute($request->validated(), $request->user()); //validacao faz sentido?

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto criado com sucesso!');
    }

    public function show(Request $request, Project $project, ShowProjectAction $action)
    {
        $project = $action->execute($project);

        return view('Project.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project); // alterar para Gate::authorize

        return view('Project.update', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectAction $action)
    {
        $project = $action->execute($project, $request->validated(), Auth::id());

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto atualizado com sucesso!');
    }
    
    public function destroy(Request $request, Project $project, DestroyProjectAction $action)
    {
        $action->execute($project);

        return redirect()->route('projects.index')
                         ->with('success', 'Projeto excluido com sucesso!');
    }
}
