<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectRequestController extends Controller
{
    public function index(Project $project): View
    {
        Gate::authorize('viewAny', [ProjectRequest::class, $project]);

        $projectRequests = $project->projectRequests()
            ->with(['clientSystem', 'task'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
        $pendingRequestsCount = $project->projectRequests()
            ->where('status', 'pending')
            ->count();

        return view('project-requests.index', compact(
            'project',
            'projectRequests',
            'pendingRequestsCount',
        ));
    }

    public function show(Project $project, ProjectRequest $projectRequest): View
    {
        abort_unless($projectRequest->project_id === $project->getKey(), 404);
        Gate::authorize('view', $projectRequest);

        $projectRequest->load(['clientSystem', 'task']);
        $pendingRequestsCount = $project->projectRequests()
            ->where('status', 'pending')
            ->count();

        return view('project-requests.show', compact(
            'project',
            'projectRequest',
            'pendingRequestsCount',
        ));
    }
}
