<?php

namespace App\Http\Requests\Task;

use App\Enums\Project\ProjectUserRole;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Task;
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
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],

            // Campo assignee_id é opcional, mas se fornecido, deve ser um inteiro 
            // e existir na tabela project_user com o mesmo project_id e ter um papel de ADMIN ou CONTRIBUTOR.
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', $this->route('project')->getKey())
                    ->whereIn('role', [
                        ProjectUserRole::ADMIN->value,
                        ProjectUserRole::CONTRIBUTOR->value,
                    ]),
            ],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
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
            'start_date.date_format' => 'O formato da data de início é inválido.',
            'due_date.date' => 'A data de vencimento deve ser uma data válida.',
            'due_date.date_format' => 'O formato da data de vencimento é inválido.',
            'due_date.after_or_equal' => 'A data de vencimento deve ser igual ou posterior à data de início.',
            'assignee_id.integer' => 'O responsável selecionado é inválido.',
            'assignee_id.exists' => 'O responsável deve ser um colaborador do projeto.',
        ];
    }
}
