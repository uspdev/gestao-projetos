<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRequestStatus;
use App\Exceptions\ProjectRequestAlreadyEvaluatedException;
use App\Http\Requests\ProjectRequest\AcceptProjectRequest;
use App\Http\Requests\ProjectRequest\RejectProjectRequest;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Tag;
use App\Models\User;
use App\Services\Tasks\TaskCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function acceptForm(Project $project, ProjectRequest $projectRequest): View|RedirectResponse
    {
        $this->authorizeRequestInProject($project, $projectRequest, 'accept');

        if (! $project->isModuleEnabled('tasks')) {
            return redirect()
                ->route('projects.requests.show', [$project, $projectRequest])
                ->with('alert-danger', 'Não é possível aceitar a Solicitação enquanto o módulo de Tarefas estiver desabilitado.');
        }

        $availableTaskTags = Tag::forTasks();
        $availableTaskAssignees = User::assignableToProject($project->id)
            ->get(['users.id', 'users.name', 'users.email']);
        $pendingRequestsCount = $project->projectRequests()
            ->where('status', 'pending')
            ->count();

        return view('project-requests.accept', compact(
            'project',
            'projectRequest',
            'availableTaskTags',
            'availableTaskAssignees',
            'pendingRequestsCount',
        ));
    }

    public function accept(
        AcceptProjectRequest $request,
        Project $project,
        ProjectRequest $projectRequest,
        TaskCreator $taskCreator,
    ): RedirectResponse
    {
        if (! $project->isModuleEnabled('tasks')) {
            return redirect()
                ->route('projects.requests.show', [$project, $projectRequest])
                ->with('alert-danger', 'Não é possível aceitar a Solicitação enquanto o módulo de Tarefas estiver desabilitado.');
        }

        $data = $request->validated();
        $response = $data['response'] ?? null;
        unset($data['response']);

        try {
            $task = $taskCreator->create(
                $project,
                $request->user(),
                $data,
                $projectRequest,
                $response,
            );
        } catch (ProjectRequestAlreadyEvaluatedException $exception) {
            return redirect()
                ->route('projects.requests.show', [$project, $projectRequest])
                ->with('alert-danger', $exception->getMessage());
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('alert-success', 'Solicitação aceita e Tarefa criada com sucesso!');
    }

    public function reject(
        RejectProjectRequest $request,
        Project $project,
        ProjectRequest $projectRequest,
    ): RedirectResponse
    {
        $rejected = DB::transaction(function () use ($request, $project, $projectRequest): bool {
            $lockedRequest = ProjectRequest::query()
                ->whereKey($projectRequest->getKey())
                ->where('project_id', $project->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== ProjectRequestStatus::PENDING) {
                return false;
            }

            $lockedRequest->forceFill([
                'status' => ProjectRequestStatus::REJECTED,
                'response' => $request->validated('response'),
                'evaluated_by' => $request->user()->id,
                'evaluated_at' => now(),
            ])->save();

            return true;
        });

        return redirect()
            ->route('projects.requests.show', [$project, $projectRequest])
            ->with(
                $rejected ? 'alert-success' : 'alert-danger',
                $rejected ? 'Solicitação rejeitada com sucesso.' : 'Esta Solicitação já foi avaliada.',
            );
    }

    private function authorizeRequestInProject(
        Project $project,
        ProjectRequest $projectRequest,
        string $ability,
    ): void {
        abort_unless($projectRequest->project_id === $project->getKey(), 404);
        Gate::authorize($ability, $projectRequest);
    }
}
