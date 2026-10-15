<?php

namespace App\Http\Requests\Task;

use App\Models\Task;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return $this->user()->can('update', $task);
    }

    public function rules(): array
    {
        $rules = $this->sharedRules();

        if ($this->input('form_context') === 'task-title') {
            return ['title' => $rules['title']];
        }

        if ($this->input('form_context') === 'task-info') {
            unset($rules['title']);
        }

        return $rules;
    }
}
