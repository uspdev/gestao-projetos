<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskAssigneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return $this->user()->can('storeAssignee', $task);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Selecione um usuário para atribuir à tarefa.',
            'user_id.integer' => 'O usuário selecionado é inválido.',
            'user_id.exists' => 'O usuário selecionado não foi encontrado.',
        ];
    }
}