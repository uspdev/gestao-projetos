<?php

namespace App\Http\Requests;

use App\Models\ClientSystem;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $clientSystem = $this->route('clientSystem');

        if (! $project instanceof Project) {
            return false;
        }

        if ($clientSystem instanceof ClientSystem) {
            return $clientSystem->project_id === $project->getKey()
                && $this->user()?->can('update', $clientSystem) === true;
        }

        return $this->user()?->can('create', [ClientSystem::class, $project]) === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        $clientSystem = $this->route('clientSystem');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:120',
                Rule::unique('client_systems', 'name')
                    ->where('project_id', $project->getKey())
                    ->ignore($clientSystem instanceof ClientSystem ? $clientSystem->getKey() : null),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
        ];
    }
}
