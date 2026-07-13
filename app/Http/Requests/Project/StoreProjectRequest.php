<?php

namespace App\Http\Requests\Project;

use App\Enums\Project\ProjectPermissionInheritance;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectVisibility;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
// rule classes returned by helpers are created via Rule::*, no direct Exists import needed

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('create', Project::class)) {
            return false;
        }

        $parentId = (int) $this->input('parent_id');
        if ($parentId <= 0) {
            return true;
        }

        $parentProject = Project::find($parentId);

        return ! $parentProject || $this->user()->can('storeMember', $parentProject);
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        $parentId = $this->input('parent_id');
        $phaseId = $this->input('phase_id');

        if ($slug === null || trim((string) $slug) === '') {
            $slug = (string) $this->input('name', '');
        }

        $merge = [
            'slug' => Str::slug((string) $slug),
            'parent_id' => $parentId !== null && trim((string) $parentId) !== '' ? (int) $parentId : null,
        ];

        // Se o campo de fase foi enviado, mesmo que seja nulo ou vazio, é feita a conversão para inteiro ou null.
        // Pq a validação de fase depende do tipo do projeto,
        // e queremos garantir que o valor esteja no formato correto para a validação personalizada.
        if ($this->has('phase_id')) {
            $merge['phase_id'] = $phaseId !== null && trim((string) $phaseId) !== '' ? (int) $phaseId : null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $projectTypeId = (int) $this->input('project_type_id');
        $phaseModuleEnabled = $this->projectTypeHasPhaseModule($projectTypeId);

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
            'project_type_id' => [
                'required',
                'integer',
                Rule::exists('project_types', 'id')->where('enabled', true),
            ],
            'visibility' => ['required', Rule::enum(ProjectVisibility::class)],
            'permission_inheritance' => ['required', Rule::enum(ProjectPermissionInheritance::class)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at'),
            ],
            // A validação de phase_id é condicional, dependendo se o módulo de fases está habilitado para o tipo de projeto selecionado.
            'phase_id' => array_filter([
                Rule::requiredIf($phaseModuleEnabled),
                Rule::prohibitedIf(! $phaseModuleEnabled),
                'integer',
                $phaseModuleEnabled ? $this->phaseExistsRule($projectTypeId) : null,
            ], fn($v) => $v !== null),
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

            'parent_id.integer' => 'O projeto pai selecionado é inválido.',
            'parent_id.exists' => 'O projeto pai selecionado não existe ou não pode receber subprojetos.',

            'phase_id.required' => 'É necessário definir a fase do projeto.',
            'phase_id.integer' => 'A fase selecionada é inválida.',
            'phase_id.exists' => 'A fase selecionada é inválida.',
            'phase_id.prohibited' => 'Este tipo de projeto não possui fases configuradas.',

            'description.max' => 'A descrição é muito longa. O limite é de :max caracteres.',
            'slug.not_in' => 'Esta URL não pode ser utilizada.',
            'tags.array' => 'As tags devem ser um array válido.',
            'tags.*.integer' => 'Cada tag deve ser um ID válido.',
            'tags.*.exists' => 'Uma ou mais tags selecionadas não existem.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $parentId = (int) $this->input('parent_id');
            if ($parentId <= 0) {
                return;
            }

            $parentProject = Project::query()
                ->with('projectType')
                ->find($parentId);

            if (! $parentProject) {
                return;
            }

            if (! $parentProject->isRootProject() || ! $parentProject->isOrganizational()) {
                $validator->errors()->add(
                    'parent_id',
                    'Apenas projetos organizacionais podem receber novos subprojetos.'
                );

                return;
            }

            $projectType = ProjectType::find((int) $this->input('project_type_id'));
            if ($projectType?->slug === Project::ORGANIZATIONAL_TYPE_SLUG) {
                $validator->errors()->add(
                    'project_type_id',
                    'Um subprojeto não pode ser do tipo organizacional.'
                );
            }
        });
    }

    protected function slugBlocklist(): array
    {
        return (array) config('projetos.slug_blocklist', []);
    }

    // Verifica se o tipo de projeto possui o módulo de fases habilitado para determinar se a validação de fase deve ser aplicada.
    protected function projectTypeHasPhaseModule(int $projectTypeId): bool
    {
        if ($projectTypeId <= 0 || !Schema::hasTable('project_type_modules') || !Schema::hasTable('modules')) {
            return false;
        }

        $projectType = ProjectType::find($projectTypeId);
        if (!$projectType) {
            return false;
        }

        return $projectType->isModuleEnabled('phases');
    }
    // A regra de validação personalizada para verificar se a fase selecionada é válida para o tipo de projeto, considerando apenas fases ativas.
    protected function phaseExistsRule(int $projectTypeId)
    {
        if ($projectTypeId <= 0 || !Schema::hasTable('project_type_phases') || !Schema::hasTable('phases')) {
            return Rule::in([]);
        }

        $projectType = ProjectType::find($projectTypeId);
        if (!$projectType) {
            return Rule::in([]);
        }

        $ids = $projectType->phases()->where('is_active', true)->pluck('phases.id')->all();

        return empty($ids) ? Rule::in([]) : Rule::in($ids);
    }
}
