<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProjectStatus;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:30'],
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
