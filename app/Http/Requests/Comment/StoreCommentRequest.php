<?php

namespace App\Http\Requests\Comment;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

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
            'commentable_type' => ['required', 'string'],
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

        $commentableClass = $this->normalizeCommentableType($type);

        if (!$commentableClass) {
            return null;
        }

        return $commentableClass::query()->find($id);
    }

    private function normalizeCommentableType(string $type): ?string
    {
        $map = [
            'project' => Project::class,
            'task' => Task::class,
        ];

        $normalized = strtolower($type);
        $candidate = $map[$normalized] ?? $type;

        if (!class_exists($candidate)) {
            return null;
        }

        if (!is_subclass_of($candidate, Model::class)) {
            return null;
        }

        return $candidate;
    }
}
