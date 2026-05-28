<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('updateModule', $project);
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'enabled.required' => 'Informe se o modulo deve ficar ativo ou inativo.',
            'enabled.boolean' => 'O valor informado para o modulo e invalido.',
        ];
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }
}
