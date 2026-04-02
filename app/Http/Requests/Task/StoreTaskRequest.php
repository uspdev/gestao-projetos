<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('create', [Task::class, $project]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'label' => ['nullable', Rule::enum(TaskLabel::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título da tarefa é obrigatório.',
            'title.min' => 'O título da tarefa deve ter pelo menos :min caracteres.',
            'title.max' => 'O título da tarefa não pode exceder :max caracteres.',
            'description.max' => 'A descrição é muito longa. O limite é de :max caracteres.',
            'priority.max' => 'A prioridade não pode exceder :max caracteres.',
            'status.required' => 'É necessário definir um status para a tarefa.',
            'status.enum' => 'O status selecionado é inválido.',
            'label.enum' => 'O rótulo selecionado é inválido.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'due_date.date' => 'A data de vencimento deve ser uma data válida.',
            'due_date.after_or_equal' => 'A data de vencimento deve ser igual ou posterior à data de início.',
        ];
    }
}