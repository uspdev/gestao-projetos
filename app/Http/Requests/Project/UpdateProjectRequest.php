<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('update', $project);
    }

    protected function prepareForValidation(): void
    {
        // Intencionalmente vazio: sobrescreve o comportamento da classe pai para nao auto-preencher slug na edicao.
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->getKey() : $project;

        $rules['slug'] = [
            'sometimes',
            'filled',
            'string',
            'alpha_dash',
            'max:80',
            Rule::unique('projects', 'slug')->ignore($projectId),
            Rule::notIn($this->slugBlocklist()),
        ];

        return $rules;
    }
}
