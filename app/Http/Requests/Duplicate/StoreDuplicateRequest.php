<?php

namespace App\Http\Requests\Duplicate;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Morphs\Duplicable;
use App\Morphs\DuplicableMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class StoreDuplicateRequest extends FormRequest
{
    private ?Model $resolvedDuplicable = null;

    public function authorize(): bool
    {
        $duplicable = $this->duplicable();
        $project = $this->route('project');

        if (! $duplicable instanceof Duplicable || ! $project instanceof Project) {
            return false;
        }

        if ($duplicable->duplicationBlockReason() !== null) {
            return false;
        }

        return match (true) {
            $duplicable instanceof Task => (int) $duplicable->project_id === (int) $project->id
                && $this->user()->can('duplicate', $duplicable),
            $duplicable instanceof Meeting => $this->user()->can('duplicate', [$duplicable, $project]),
            $duplicable instanceof Project => $duplicable->is($project)
                && $this->user()->can('duplicate', $duplicable),
            default => false,
        };
    }

    public function rules(): array
    {
        $duplicable = $this->duplicable();

        if ($duplicable instanceof Task) {
            return [
                'duplication_form' => ['required', 'in:task'],
                'title' => ['required', 'string', 'min:3', 'max:120'],
                'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
                'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ];
        }

        if ($duplicable instanceof Meeting) {
            return [
                'duplication_form' => ['required', 'in:meeting'],
                'title' => ['required', 'string', 'min:3', 'max:120'],
                'scheduled_at' => ['required', 'date'],
            ];
        }

        if ($duplicable instanceof Project) {
            $rules = [
                'duplication_form' => ['required', 'in:project'],
                'name' => ['required', 'string', 'min:3', 'max:50'],
                'copy_members' => ['required', 'boolean'],
                'copy_tasks' => ['required', 'boolean'],
                'copy_meetings' => ['required', 'boolean'],
                'meeting_dates' => ['nullable', 'array'],
            ];

            if ($this->boolean('copy_meetings')) {
                foreach ($duplicable->meetings()->pluck('meetings.id') as $meetingId) {
                    $rules['meeting_dates.' . $meetingId] = ['required', 'date'];
                }
            }

            return $rules;
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O nome da cópia é obrigatório.',
            'title.min' => 'O nome da cópia deve ter pelo menos :min caracteres.',
            'title.max' => 'O nome da cópia não pode exceder :max caracteres.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'start_date.date_format' => 'O formato da data de início é inválido.',
            'due_date.date' => 'A data de vencimento deve ser uma data válida.',
            'due_date.date_format' => 'O formato da data de vencimento é inválido.',
            'due_date.after_or_equal' => 'A data de vencimento deve ser igual ou posterior à data de início.',
            'scheduled_at.required' => 'Informe a nova data e hora da reunião.',
            'scheduled_at.date' => 'A data e hora da reunião deve ser válida.',
            'name.required' => 'O nome do projeto é obrigatório.',
            'name.min' => 'O nome do projeto deve ter pelo menos :min caracteres.',
            'name.max' => 'O nome do projeto não pode exceder :max caracteres.',
            'copy_members.required' => 'Informe se os membros devem ser copiados.',
            'copy_tasks.required' => 'Informe se as tarefas devem ser copiadas.',
            'copy_meetings.required' => 'Informe se as reuniões devem ser copiadas.',
            'meeting_dates.*.required' => 'Informe a nova data e hora de cada reunião selecionada.',
            'meeting_dates.*.date' => 'A nova data e hora da reunião deve ser válida.',
        ];
    }

    public function duplicable(): ?Model
    {
        if ($this->resolvedDuplicable) {
            return $this->resolvedDuplicable;
        }

        $type = (string) $this->route('duplicable_type', '');
        $id = $this->route('duplicable_id');
        $class = DuplicableMap::resolveClass($type);

        if (! $class || ! ctype_digit((string) $id) || (int) $id <= 0) {
            return null;
        }

        return $this->resolvedDuplicable = $class::query()->find($id);
    }

    public function duplicationOptions(): array
    {
        $duplicable = $this->duplicable();
        $data = $this->validated();

        return match (true) {
            $duplicable instanceof Task => [
                'title' => $data['title'],
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ],
            $duplicable instanceof Meeting => [
                'title' => $data['title'],
                'scheduled_at' => $data['scheduled_at'],
            ],
            $duplicable instanceof Project => [
                'name' => $data['name'],
                'copy_members' => (bool) $data['copy_members'],
                'copy_tasks' => (bool) $data['copy_tasks'],
                'copy_meetings' => (bool) $data['copy_meetings'],
                'meeting_dates' => $data['meeting_dates'] ?? [],
            ],
            default => [],
        };
    }
}
