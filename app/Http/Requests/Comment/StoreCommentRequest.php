<?php

namespace App\Http\Requests\Comment;

use App\Morphs\CommentableMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $commentable = $this->commentable();

        if (!$commentable) {
            return false;
        }

        return $this->user()->can('comment', $commentable);
    }

    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', Rule::in(CommentableMap::allowedValues())],
            'commentable_id' => ['required', 'integer'],
            'text' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentable_type.required' => 'Informe o tipo da entidade comentada.',
            'commentable_type.string' => 'O tipo da entidade comentada é inválido.',
            'commentable_type.in' => 'O tipo da entidade comentada é inválido.',
            'commentable_id.required' => 'Informe a entidade comentada.',
            'commentable_id.integer' => 'A entidade comentada é inválida.',
            'text.required' => 'O comentário é obrigatório.',
            'text.max' => 'O comentário é muito longo. O limite é de :max caracteres.',
            'parent_id.integer' => 'O comentário pai é inválido.',
            'parent_id.exists' => 'O comentário pai não foi encontrado.',
        ];
    }

    public function commentable(): ?Model
    {
        $type = (string) $this->input('commentable_type', '');
        $id = $this->input('commentable_id');

        if ($type === '' || !$id) {
            return null;
        }

        $commentableClass = CommentableMap::resolveClass($type);

        if (!$commentableClass) {
            return null;
        }

        return $commentableClass::query()->find($id);
    }
}
