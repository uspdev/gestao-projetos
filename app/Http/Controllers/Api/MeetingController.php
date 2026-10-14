<?php

namespace App\Http\Controllers\Api;

use App\Enums\Meeting\MeetingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Meeting\MeetingDetailResource;
use App\Http\Resources\Meeting\MeetingResource;
use App\Models\Project;
use App\Services\Api\ProjectDetailLoader;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->ensureProjectIsInScope($request, $project);
        $this->ensureMeetingsModuleIsEnabled($project);

        $validated = $this->validateIndex($request);
        $meetings = $project->meetings()
            ->with(['projects' => fn ($query) => $query->orderBy('name')->orderBy('id')])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->whereIn('status', $validated['status']),
            )
            ->when(
                isset($validated['scheduled_from']),
                fn ($query) => $query->where('scheduled_at', '>=', $this->scheduledBound($validated['scheduled_from'], true)),
            )
            ->when(
                isset($validated['scheduled_to']),
                fn ($query) => $query->where('scheduled_at', '<=', $this->scheduledBound($validated['scheduled_to'], false)),
            )
            ->when(
                isset($validated['search']) && $validated['search'] !== '',
                fn ($query) => $query->where(fn ($search) => $search
                    ->whereLike('title', '%'.$validated['search'].'%')
                    ->orWhereLike('location', '%'.$validated['search'].'%')),
            )
            ->orderByDesc('scheduled_at')
            ->orderByDesc('meetings.id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return MeetingResource::collection($meetings);
    }

    public function show(
        Request $request,
        Project $project,
        int $meeting,
        ProjectDetailLoader $details,
    ): MeetingDetailResource
    {
        $this->ensureProjectIsInScope($request, $project);
        $this->ensureMeetingsModuleIsEnabled($project);

        $meeting = $project->meetings()->findOrFail($meeting);

        return new MeetingDetailResource($details->loadMeeting($meeting, $project));
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

    private function ensureMeetingsModuleIsEnabled(Project $project): void
    {
        if ($project->isModuleEnabled('meetings')) {
            return;
        }

        abort(response()->json([
            'message' => 'O módulo de Reuniões está desabilitado neste Projeto.',
            'code' => 'meetings_module_disabled',
        ], 409));
    }

    /** @return array<string, mixed> */
    private function validateIndex(Request $request): array
    {
        $input = $request->query();

        if (array_key_exists('status', $input) && ! is_array($input['status'])) {
            $input['status'] = [$input['status']];
        }

        return Validator::make($input, [
            'status' => ['sometimes', 'array', 'min:1'],
            'status.*' => ['required', Rule::enum(MeetingStatus::class)],
            'scheduled_from' => ['sometimes', $this->isoDateRule()],
            'scheduled_to' => ['sometimes', $this->isoDateRule(), 'after_or_equal:scheduled_from'],
            'search' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ])->validate();
    }

    private function scheduledBound(string $value, bool $lowerBound): CarbonImmutable
    {
        $date = CarbonImmutable::parse($value)->setTimezone(config('app.timezone'));

        // A coluna scheduled_at armazena segundos; preserve limites inclusivos com frações.
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
