<?php

namespace App\Http\Controllers;

use App\Http\Requests\Phases\UpdateProjectPhaseRequest;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PhaseController extends Controller
{
    /**
     * Atualiza a fase de um projeto.
     *
     * @param  \App\Http\Requests\Phases\UpdateProjectPhaseRequest $request
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectPhaseRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $project->update($data);
        });

        return redirect()->back()
            ->withFragment(deep_link_fragment($project))
            ->with('alert-success', 'Fase do projeto atualizada com sucesso!');
    }
}
