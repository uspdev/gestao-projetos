<?php

namespace App\Http\Requests\ProjectRequest;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Models\Project;
use App\Models\ProjectRequest;

class AcceptProjectRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $projectRequest = $this->route('projectRequest');

        return $project instanceof Project
            && $projectRequest instanceof ProjectRequest
            && $projectRequest->project_id === $project->getKey()
            && $this->user()?->can('accept', $projectRequest) === true;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'response' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'response.max' => 'A Resposta à Solicitação não pode exceder :max caracteres.',
        ];
    }
}
