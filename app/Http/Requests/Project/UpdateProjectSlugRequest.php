<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProjectSlugRequest extends FormRequest
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
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->getKey() : $project;

        return [
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:80',
                Rule::unique('projects', 'slug')->ignore($projectId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'A URL (slug) é obrigatória.',
            'slug.alpha_dash' => 'Use apenas letras minúsculas, números e hifens.',
            'slug.max' => 'A URL pode ter no máximo 80 caracteres.',
            'slug.unique' => 'Esta URL já está sendo usada por outro projeto.',
        ];
    }
}
