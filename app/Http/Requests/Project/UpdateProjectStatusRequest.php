<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'E necessario definir um status para o projeto.',
            'status.enum' => 'O status selecionado e invalido.',
        ];
    }
}
