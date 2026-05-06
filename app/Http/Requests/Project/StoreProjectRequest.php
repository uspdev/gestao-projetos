<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectStatus;
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
