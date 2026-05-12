<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectPermissionInheritance;
use App\Enums\Project\ProjectPhase;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectVisibility;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');

        if ($slug === null || trim((string) $slug) === '') {
            $slug = (string) $this->input('name', '');
        }

        $this->merge([
            'slug' => Str::slug((string) $slug),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:80',
                Rule::unique('projects', 'slug'),
                Rule::notIn($this->slugBlocklist()),
            ],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'project_type_id' => ['nullable', 'integer', 'exists:project_types,id'],
            'visibility' => ['required', Rule::enum(ProjectVisibility::class)],
            'permission_inheritance' => ['required', Rule::enum(ProjectPermissionInheritance::class)],
            'phase' => ['required', Rule::enum(ProjectPhase::class)],
            'description' => ['nullable', 'string', 'max:10000'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do projeto é obrigatório.',
            'name.min' => 'O nome do projeto deve ter pelo menos :min caracteres.',
            'name.max' => 'O nome do projeto não pode exceder :max caracteres.',

            'status.required' => 'É necessário definir um status para o projeto.',
            'status.enum' => 'O status selecionado é inválido.',

            'project_type_id.exists' => 'O tipo de projeto selecionado é inválido.',

            'visibility.required' => 'É necessário definir a visibilidade do projeto.',
            'visibility.enum' => 'A visibilidade selecionada é inválida.',

            'permission_inheritance.required' => 'É necessário definir a herança de permissões.',
            'permission_inheritance.enum' => 'A herança de permissões selecionada é inválida.',

            'phase.required' => 'É necessário definir a fase do projeto.',
            'phase.enum' => 'A fase selecionada é inválida.',

            'description.max' => 'A descrição é muito longa. O limite é de :max caracteres.',
            'slug.not_in' => 'Esta URL não pode ser utilizada.',
            'tags.array' => 'As tags devem ser um array válido.',
            'tags.*.integer' => 'Cada tag deve ser um ID válido.',
            'tags.*.exists' => 'Uma ou mais tags selecionadas não existem.',
        ];
    }

    protected function slugBlocklist(): array
    {
        return (array) config('projects.slug_blocklist', []);
    }
}
