<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Support\Str;
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
        if ($this->has('slug')) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('slug')),
            ]);
        }
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
