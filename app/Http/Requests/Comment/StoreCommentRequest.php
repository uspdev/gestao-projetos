<?php

namespace App\Http\Requests\Comment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $commentable = $this->resolveCommentable();

        if (!$commentable) {
            return false;
        }

        return $this->user()->can('view', $commentable);
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'O comentário é obrigatório.',
            'text.max' => 'O comentário é muito longo. O limite é de :max caracteres.',
            'parent_id.integer' => 'O comentário pai é inválido.',
            'parent_id.exists' => 'O comentário pai não foi encontrado.',
        ];
    }

    protected function resolveCommentable(): ?Model
    {
        foreach (['project', 'task', 'meeting', 'meetingItem'] as $param) {
            $value = $this->route($param);

            if ($value instanceof Model) {
                return $value;
            }
        }

        return null;
    }
}
