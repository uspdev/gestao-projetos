<?php

namespace App\Http\Requests\Meeting;

use App\Enums\Meeting\MeetingStatus;
use App\Models\Meeting;
use App\Models\Module;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (!$project instanceof Project) {
            return false;
        }

        return $this->user()->can('create', [Meeting::class, $project]);
    }

    protected function prepareForValidation(): void
    {
        $project = $this->route('project');

        if (!$project instanceof Project) {
            return;
        }

        $projects = $this->input('projects', []);
        $projects = is_array($projects) ? $projects : [];
        $projects[] = $project->getKey();

        $projects = collect($projects)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $this->merge(['projects' => $projects]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(MeetingStatus::class)],
            'projects' => ['required', 'array', 'min:1'],
            'projects.*' => ['integer', 'exists:projects,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O titulo da reuniao e obrigatorio.',
            'title.min' => 'O titulo da reuniao deve ter pelo menos :min caracteres.',
            'title.max' => 'O titulo da reuniao nao pode exceder :max caracteres.',
            'location.max' => 'O local da reuniao nao pode exceder :max caracteres.',
            'notes.max' => 'As notas sao muito longas. O limite e de :max caracteres.',
            'status.required' => 'E necessario definir um status para a reuniao.',
            'status.enum' => 'O status selecionado e invalido.',
            'projects.required' => 'Selecione pelo menos um projeto.',
            'projects.array' => 'Os projetos devem ser um array valido.',
            'projects.min' => 'Selecione pelo menos um projeto.',
            'projects.*.integer' => 'Cada projeto deve ser um ID valido.',
            'projects.*.exists' => 'Um ou mais projetos selecionados nao existem.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $projectIds = collect($this->input('projects', []))
                ->filter(fn($value) => $value !== null && $value !== '')
                ->map(fn($value) => (int) $value)
                ->unique()
                ->values()
                ->all();

            if (empty($projectIds)) {
                return;
            }

            $projects = Project::query()
                ->whereIn('id', $projectIds)
                ->get();

            foreach ($projects as $project) {
                if (!Module::isEnabledForProject($project, 'meetings')) {
                    $validator->errors()->add(
                        'projects',
                        'Um ou mais projetos estao com o modulo de reunioes desativado.'
                    );
                    break;
                }
            }
        });
    }
}
