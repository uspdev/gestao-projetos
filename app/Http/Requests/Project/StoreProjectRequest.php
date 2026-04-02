<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool 
    {
        return $this->user()->can('create', Project::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
        {
            return [
                'name.required' => 'O nome do projeto é obrigatório.',
                'name.min' => 'O nome do projeto deve ter pelo menos :min caracteres.',
                'name.max' => 'O nome do projeto não pode exceder :max caracteres.',
                
                'status.required' => 'É necessário definir um status para o projeto.',
                'status.enum' => 'O status selecionado é inválido.',
                
                'description.max' => 'A descrição é muito longa. O limite é de :max caracteres.',
            ];
        }

}
