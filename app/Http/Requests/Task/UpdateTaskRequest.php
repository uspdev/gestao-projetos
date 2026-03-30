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
        $rules = parent::rules();

        return $rules;
    }
}