<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectPhase;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'phase' => ['required', Rule::enum(ProjectPhase::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'phase.required' => 'E necessario definir uma fase para o projeto.',
            'phase.enum' => 'A fase selecionada e invalida.',
        ];
    }
}
