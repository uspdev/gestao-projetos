<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectUserRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('storeMember', $project);
    }

    public function rules(): array
    {
        return [
            'codpes' => [
                'required',
                'integer',
            ],
            'role' => ['required', Rule::enum(ProjectUserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'codpes.required' => 'Selecione um usuário para adicionar ao projeto.',
            'codpes.integer' => 'O usuário selecionado é inválido.',
            'role.required' => 'Selecione a role do membro no projeto.',
            'role.enum' => 'A role selecionada é inválida.',
        ];
    }
}
