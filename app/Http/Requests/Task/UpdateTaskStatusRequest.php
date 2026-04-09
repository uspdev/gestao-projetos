<?php

namespace App\Http\Requests\Task;

use App\Enums\Task\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return $this->user()->can('update', $task);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'É necessário definir um status para a tarefa.',
            'status.enum' => 'O status selecionado é inválido.',
        ];
    }
}