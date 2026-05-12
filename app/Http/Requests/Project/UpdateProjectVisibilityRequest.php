<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectVisibility;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectVisibilityRequest extends FormRequest
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
            'visibility' => ['required', Rule::enum(ProjectVisibility::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'visibility.required' => 'E necessario definir a visibilidade do projeto.',
            'visibility.enum' => 'A visibilidade selecionada e invalida.',
        ];
    }
}
