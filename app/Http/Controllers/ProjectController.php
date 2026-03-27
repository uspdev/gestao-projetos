<?php

namespace App\Http\Controllers;

use App\Actions\Project\IndexProjectAction;
use App\Actions\Project\ShowProjectAction;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\ShowProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\UpdateProjectAction;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(IndexProjectRequest $request, IndexProjectAction $action)
    {
        $projects = $action->execute($request->user());

        return view('Project.index', compact('projects'));
    }

    public function create()
    {
        //
    }

    public function store(StoreProjectRequest $request, CreateProjectAction $action)
    {
        $project = $action->execute($request->validated(), Auth::id());

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto criado com sucesso!');
    }

    public function show(ShowProjectRequest $request, Project $project, ShowProjectAction $action)
    {
        $project = $action->execute($project);

        return view('Project.show', compact('project'));
    }

    public function edit(Project $project)
    {
        //
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectAction $action)
    {
        $project = $action->execute($project, $request->validated(), Auth::id());

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Projeto atualizado com sucesso!');
    }
    
    public function destroy(Project $project)
    {
        //
    }
}
