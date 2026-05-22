<?php

namespace App\Http\Requests\Phases;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Exists;

class UpdateProjectPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('update', $project);
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        $projectTypeId = (int) ($project?->project_type_id ?? 0);

        return [
            'phase_id' => [
                'required',
                'integer',
                $this->phaseExistsRule($projectTypeId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phase_id.required' => 'E necessario definir uma fase para o projeto.',
            'phase_id.integer' => 'A fase selecionada e invalida.',
            'phase_id.exists' => 'A fase selecionada e invalida.',
        ];
    }

    protected function phaseExistsRule(int $projectTypeId): Exists
    {
        return Rule::exists('phases', 'id')->where(function ($query) use ($projectTypeId) {
            $query->where('is_active', true);

            if ($projectTypeId <= 0 || !Schema::hasTable('project_type_phases')) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereIn('id', function ($sub) use ($projectTypeId) {
                $sub->select('phase_id')
                    ->from('project_type_phases')
                    ->where('project_type_id', $projectTypeId);
            });
        });
    }
}
