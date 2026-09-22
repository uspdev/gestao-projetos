<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileResource;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->ensureProjectIsInScope($request, $project);

        $tasksEnabled = $project->isModuleEnabled('tasks');
        $meetingsEnabled = $project->isModuleEnabled('meetings');
        $validated = $this->validateIndex($request);

        $files = Media::query()
            ->with('model')
            ->where(fn (Builder $query): Builder => $query
                ->whereHasMorph('model', Project::class)
                ->orWhereHasMorph('model', Task::class)
                ->orWhereHasMorph('model', Meeting::class))
            ->where(function (Builder $query) use ($project, $tasksEnabled, $meetingsEnabled): void {
                $query->where(fn (Builder $projectFiles): Builder => $projectFiles
                    ->where('model_type', $project->getMorphClass())
                    ->where('model_id', $project->getKey()));

                if ($tasksEnabled) {
                    $query->orWhere(fn (Builder $taskFiles): Builder => $taskFiles
                        ->where('model_type', (new Task())->getMorphClass())
                        ->whereHasMorph(
                            'model',
                            Task::class,
                            fn (Builder $tasks): Builder => $tasks->where('project_id', $project->getKey()),
                        ));
                }

                if ($meetingsEnabled) {
                    $query
                        ->orWhere(fn (Builder $meetingFiles): Builder => $meetingFiles
                            ->where('model_type', (new Meeting())->getMorphClass())
                            ->whereHasMorph(
                                'model',
                                Meeting::class,
                                fn (Builder $meetings): Builder => $meetings->whereHas(
                                    'projects',
                                    fn (Builder $projects): Builder => $projects->whereKey($project->getKey()),
                                ),
                            ))
                        ->orWhereHas(
                            'sharedWithMeetings',
                            fn (Builder $meetings): Builder => $meetings->whereHas(
                                'projects',
                                fn (Builder $projects): Builder => $projects->whereKey($project->getKey()),
                            ),
                        );
                }
            })
            ->when(
                isset($validated['search']) && $validated['search'] !== '',
                fn (Builder $query): Builder => $query->whereLike('name', '%'.$validated['search'].'%'),
            )
            ->when(
                isset($validated['owner_type']),
                fn (Builder $query): Builder => $query->whereIn('model_type', collect($validated['owner_type'])
                    ->map(fn (string $type): string => match ($type) {
                        'project' => (new Project())->getMorphClass(),
                        'task' => (new Task())->getMorphClass(),
                        'meeting' => (new Meeting())->getMorphClass(),
                    })
                    ->all()),
            )
            ->when(
                isset($validated['mime_type']),
                fn (Builder $query): Builder => $query->whereIn('mime_type', $validated['mime_type']),
            )
            ->when(
                isset($validated['uploaded_from']),
                fn (Builder $query): Builder => $query->where(
                    'created_at',
                    '>=',
                    $this->uploadedBound($validated['uploaded_from'], true),
                ),
            )
            ->when(
                isset($validated['uploaded_to']),
                fn (Builder $query): Builder => $query->where(
                    'created_at',
                    '<=',
                    $this->uploadedBound($validated['uploaded_to'], false),
                ),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return FileResource::collection($files);
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

    /** @return array<string, mixed> */
    private function validateIndex(Request $request): array
    {
        $input = $request->query();

        foreach (['owner_type', 'mime_type'] as $filter) {
            if (array_key_exists($filter, $input) && ! is_array($input[$filter])) {
                $input[$filter] = [$input[$filter]];
            }
        }

        return Validator::make($input, [
            'search' => ['sometimes', 'string', 'max:255'],
            'owner_type' => ['sometimes', 'array', 'min:1'],
            'owner_type.*' => ['required', 'string', Rule::in(['project', 'task', 'meeting'])],
            'mime_type' => ['sometimes', 'array', 'min:1'],
            'mime_type.*' => ['required', 'string', 'max:255'],
            'uploaded_from' => ['sometimes', $this->isoDateRule()],
            'uploaded_to' => ['sometimes', $this->isoDateRule(), 'after_or_equal:uploaded_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ])->validate();
    }

    private function uploadedBound(string $value, bool $lowerBound): CarbonImmutable
    {
        $date = CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));

        return $lowerBound && $date->microsecond > 0
            ? $date->startOfSecond()->addSecond()
            : $date->startOfSecond();
    }

    private function isoDateRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            if (! is_string($value) || ! preg_match(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/',
                $value,
            )) {
                $fail("O campo {$attribute} deve ser uma data ISO 8601 válida.");

                return;
            }

            $format = str_contains($value, '.') ? '!Y-m-d\TH:i:s.uP' : '!Y-m-d\TH:i:sP';
            $date = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                $fail("O campo {$attribute} deve ser uma data ISO 8601 válida.");
            }
        };
    }
}
