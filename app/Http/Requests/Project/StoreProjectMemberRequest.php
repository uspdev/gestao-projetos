<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Models\ProjectUserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
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
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'role' => ['required', Rule::enum(ProjectUserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Selecione um usuário para adicionar ao projeto.',
            'user_id.integer' => 'O usuário selecionado é inválido.',
            'user_id.exists' => 'O usuário selecionado não foi encontrado.',
            'role.required' => 'Selecione a role do membro no projeto.',
            'role.enum' => 'A role selecionada é inválida.',
        ];
    }
}