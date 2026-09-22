<?php

namespace App\Http\Controllers\Api;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Tag;
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
            ->when(
                isset($validated['priority']),
                fn ($query) => $query->whereIn('priority', $validated['priority']),
            )
            ->when(
                isset($validated['due_from']),
                fn ($query) => $query->whereDate('due_date', '>=', $validated['due_from']),
            )
            ->when(
                isset($validated['due_to']),
                fn ($query) => $query->whereDate('due_date', '<=', $validated['due_to']),
            )
            ->when(
                isset($validated['tag']),
                fn ($query) => $query->whereHas('tags', fn ($tags) => $tags
                    ->where('type', Tag::TYPE_TASK)
                    ->whereIn('slug->'.Tag::getLocale(), $validated['tag'])),
            )
            ->when(
                isset($validated['search']) && $validated['search'] !== '',
                fn ($query) => $query->where(fn ($search) => $search
                    ->whereLike('title', '%'.$validated['search'].'%')
                    ->orWhereLike('description', '%'.$validated['search'].'%')),
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
            $owner instanceof Project && (int) $owner->getKey() === (int) $project->getKey(),
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
     * @return array<string, mixed>
     */
    private function validateIndex(Request $request): array
    {
        $input = $request->query();

        foreach (['status', 'priority', 'tag'] as $filter) {
            if (array_key_exists($filter, $input) && ! is_array($input[$filter])) {
                $input[$filter] = [$input[$filter]];
            }
        }

        return Validator::make($input, [
            'status' => ['sometimes', 'array', 'min:1'],
            'status.*' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'array', 'min:1'],
            'priority.*' => ['required', 'integer', Rule::enum(TaskPriority::class)],
            'due_from' => ['sometimes', 'date_format:Y-m-d'],
            'due_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:due_from'],
            'tag' => ['sometimes', 'array', 'min:1'],
            'tag.*' => ['required', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ])->validate();
    }
}
