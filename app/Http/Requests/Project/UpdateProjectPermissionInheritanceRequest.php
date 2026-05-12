<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectPermissionInheritance;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectPermissionInheritanceRequest extends FormRequest
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
            'permission_inheritance' => ['required', Rule::enum(ProjectPermissionInheritance::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_inheritance.required' => 'E necessario definir a heranca de permissoes.',
            'permission_inheritance.enum' => 'A heranca de permissoes selecionada e invalida.',
        ];
    }
}
