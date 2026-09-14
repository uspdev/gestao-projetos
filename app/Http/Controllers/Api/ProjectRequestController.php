<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Resources\ProjectRequestResource;
use App\Models\ClientSystem;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjectRequestController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $clientSystem = $this->clientSystemInScope($request, $project);
        $validated = $this->validateIndex($request);

        $projectRequests = $project->projectRequests()
            ->where('client_system_id', $clientSystem->getKey())
            ->with('task')
            ->when(
                isset($validated['status']),
                fn ($query) => $query->whereIn('status', $validated['status']),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return ProjectRequestResource::collection($projectRequests);
    }

    public function store(StoreProjectRequest $request, Project $project): JsonResponse
    {
        $clientSystem = $this->clientSystemInScope($request, $project);

        $this->ensureTasksModuleIsEnabled($project);

        $projectRequest = $project->projectRequests()->create([
            ...$request->validated(),
            'client_system_id' => $clientSystem->getKey(),
            'status' => ProjectRequestStatus::PENDING,
        ]);

        return (new ProjectRequestResource($projectRequest))
            ->response()
            ->setStatusCode(201)
            ->header(
                'Location',
                route('api.projects.requests.show', [$project, $projectRequest]),
            );
    }

    public function show(Request $request, Project $project, int $projectRequest): ProjectRequestResource
    {
        $clientSystem = $this->clientSystemInScope($request, $project);

        $projectRequest = $project->projectRequests()
            ->where('client_system_id', $clientSystem->getKey())
            ->with('task')
            ->findOrFail($projectRequest);

        return new ProjectRequestResource($projectRequest);
    }

    private function clientSystemInScope(Request $request, Project $project): ClientSystem
    {
        $apiKey = $request->attributes->get(
            (string) config('api-keys.middleware.request_attribute', 'apiKey')
        );
        $owner = $apiKey?->owner;

        abort_unless(
            $owner instanceof ClientSystem
                && (int) $owner->project_id === (int) $project->getKey(),
            404,
        );

        return $owner;
    }

    private function ensureTasksModuleIsEnabled(Project $project): void
    {
        if ($project->isModuleEnabled('tasks')) {
            return;
        }

        abort(response()->json([
            'message' => 'O módulo de Tarefas está desabilitado neste Projeto.',
            'code' => 'tasks_module_disabled',
        ], 409));
    }

    /**
     * @return array{status?: list<string>, per_page?: int}
     */
    private function validateIndex(Request $request): array
    {
        $input = $request->query();

        if (array_key_exists('status', $input) && ! is_array($input['status'])) {
            $input['status'] = [$input['status']];
        }

        return Validator::make($input, [
            'status' => ['sometimes', 'array', 'min:1'],
            'status.*' => ['required', Rule::enum(ProjectRequestStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ])->validate();
    }
}
