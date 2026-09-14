<?php

namespace App\Http\Requests\ProjectRequest;

use App\Models\Project;
use App\Models\ProjectRequest;
use Illuminate\Foundation\Http\FormRequest;

class RejectProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $projectRequest = $this->route('projectRequest');

        return $project instanceof Project
            && $projectRequest instanceof ProjectRequest
            && $projectRequest->project_id === $project->getKey()
            && $this->user()?->can('reject', $projectRequest) === true;
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'A Resposta à Solicitação é obrigatória para rejeitar a proposta.',
            'response.max' => 'A Resposta à Solicitação não pode exceder :max caracteres.',
        ];
    }
}
