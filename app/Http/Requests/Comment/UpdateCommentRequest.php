<?php

namespace App\Http\Requests\Comment;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        if (! $comment instanceof Comment) {
            return false;
        }

        return $this->user()->can('update', $comment);
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'O comentario e obrigatorio.',
            'text.max' => 'O comentario e muito longo. O limite e de :max caracteres.',
        ];
    }
}
