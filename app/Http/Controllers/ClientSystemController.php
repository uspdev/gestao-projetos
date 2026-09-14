<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientSystemRequest;
use App\Models\ClientSystem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClientSystemController extends Controller
{
    public function store(ClientSystemRequest $request, Project $project): RedirectResponse
    {
        $project->clientSystems()->create($request->validated());

        return redirect()
            ->route('projects.settings', $project)
            ->withFragment('project-integrations-settings')
            ->with('alert-success', 'Sistema cliente criado com sucesso!');
    }

    public function update(
        ClientSystemRequest $request,
        Project $project,
        ClientSystem $clientSystem,
    ): RedirectResponse {
        $this->ensureBelongsToProject($clientSystem, $project);
        $clientSystem->update($request->validated());

        return redirect()
            ->route('projects.settings', $project)
            ->withFragment('project-integrations-settings')
            ->with('alert-success', 'Sistema cliente atualizado com sucesso!');
    }

    public function apiKeys(Project $project, ClientSystem $clientSystem): View
    {
        $this->ensureBelongsToProject($clientSystem, $project);
        Gate::authorize('manageApiKeys', $clientSystem);

        return view('client-systems.api-keys', compact('project', 'clientSystem'));
    }

    private function ensureBelongsToProject(ClientSystem $clientSystem, Project $project): void
    {
        abort_unless($clientSystem->project_id === $project->getKey(), 404);
    }
}
