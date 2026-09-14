<?php

namespace App\Http\Controllers\Api;

use App\Enums\Task\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\ClientSystem;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->ensureProjectIsInScope($request, $project);
        $this->ensureTasksModuleIsEnabled($project);

        $validated = $this->validateIndex($request);

        $tasks = $project->tasks()
            ->with([
                'tags',
                'users' => fn ($query) => $query->orderBy('name'),
            ])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->whereIn('status', $validated['status']),
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    public function show(Request $request, Project $project, int $task): TaskResource
    {
        $this->ensureProjectIsInScope($request, $project);
        $this->ensureTasksModuleIsEnabled($project);

        $task = $project->tasks()
            ->with([
                'tags',
                'users' => fn ($query) => $query->orderBy('name'),
            ])
            ->findOrFail($task);

        return new TaskResource($task);
    }

    private function ensureProjectIsInScope(Request $request, Project $project): void
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
            'status.*' => ['required', Rule::enum(TaskStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ])->validate();
    }
}
