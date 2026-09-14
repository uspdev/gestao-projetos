<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trimmed = [];

        foreach (['title', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $trimmed[$field] = trim($this->input($field));
            }
        }

        $this->merge($trimmed);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['required', 'string', 'max:10000'],
            'source_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'project_id' => ['missing'],
            'project' => ['missing'],
            'client_system_id' => ['missing'],
            'client_system' => ['missing'],
            'status' => ['missing'],
            'response' => ['missing'],
            'evaluated_by' => ['missing'],
            'evaluator' => ['missing'],
            'evaluated_at' => ['missing'],
            'task_id' => ['missing'],
            'task' => ['missing'],
        ];
    }
}
