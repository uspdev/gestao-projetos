<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\UpdateProjectAction;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        //
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

    public function show(Project $project)
    {
        //
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
