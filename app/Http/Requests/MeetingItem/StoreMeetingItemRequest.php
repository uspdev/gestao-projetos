<?php

namespace App\Http\Requests\MeetingItem;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use App\Enums\Meeting\MeetingStatus;

class StoreMeetingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meeting = $this->route('meeting');
        $project = $this->route('project');

        if (!$meeting instanceof Meeting || !$project instanceof Project) {
            return false;
        }

        if (!$this->user()->can('update', [$meeting, $project])) {
            return false;
        }

        if ($meeting->status === MeetingStatus::COMPLETED) {
            throw new AuthorizationException('Não é possível adicionar itens a uma reunião já concluída.');
        }

        $discussable = $this->discussable();

        if (!$discussable) {
            abort(404, 'O item (projeto ou tarefa) que você está tentando vincular não foi encontrado.');
        }

        $projectId = $discussable instanceof Project
            ? $discussable->id
            : $discussable->project_id;

        if (!$meeting->projects()->where('projects.id', $projectId)->exists()) {
            throw new AuthorizationException('Este item pertence a um projeto que não está vinculado a esta reunião.');
        }

        return true;
    }

    public function rules(): array
    {
        $meeting = $this->route('meeting');

        // Resolve a classe completa baseada no input (ex: 'task' vira 'App\Models\Task')
        $typeInput = (string) $this->input('discussable_type', '');
        $discussableClass = $this->resolveDiscussableClass($typeInput);

        return [
            'discussable_type' => ['required', 'string', Rule::in(['project', 'task'])],
            'order'            => ['required', 'integer', 'min:1'],

            'discussable_id'   => [
                'required',
                'integer',
                Rule::unique('meeting_items')->where(function ($query) use ($meeting, $discussableClass) {
                    return $query->where('meeting_id', $meeting->id)
                        ->where('discussable_type', $discussableClass);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'discussable_type.required' => 'Informe o tipo do item de pauta.',
            'discussable_type.string' => 'O tipo do item de pauta e invalido.',
            'discussable_type.in' => 'O tipo do item de pauta e invalido.',
            'discussable_id.required' => 'Informe o item de pauta.',
            'discussable_id.integer' => 'O item de pauta e invalido.',
            'discussable_id.unique' => 'Este item já foi adicionado à pauta desta reunião.',
            'order.required' => 'Informe a ordem do item.',
            'order.integer' => 'A ordem do item e invalida.',
            'order.min' => 'A ordem do item deve ser maior ou igual a :min.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $meeting = $this->route('meeting');
            if (!$meeting instanceof Meeting) {
                return;
            }

            $discussable = $this->discussable();
            if (!$discussable) {
                $validator->errors()->add('discussable_id', 'O item de pauta nao foi encontrado.');
                return;
            }

            $parentProjectId = $this->resolveParentProjectId($discussable);
            if (!$parentProjectId) {
                $validator->errors()->add('discussable_id', 'O item de pauta e invalido.');
                return;
            }

            $linked = $meeting->projects()
                ->where('projects.id', $parentProjectId)
                ->exists();

            if (!$linked) {
                $validator->errors()->add(
                    'discussable_id',
                    'A reuniao nao esta vinculada ao projeto pai deste item.'
                );
            }
        });
    }

    public function discussable(): ?Model
    {
        $type = (string) $this->input('discussable_type', '');
        $id = $this->input('discussable_id');

        if ($type === '' || !$id) {
            return null;
        }

        $class = $this->resolveDiscussableClass($type);
        if (!$class) {
            return null;
        }

        return $class::query()->find($id);
    }

    private function resolveDiscussableClass(string $type): ?string
    {
        return match (strtolower($type)) {
            'project' => Project::class,
            'task' => Task::class,
            default => null,
        };
    }

    private function resolveParentProjectId(Model $discussable): ?int
    {
        if ($discussable instanceof Task) {
            return $discussable->project_id ?: null;
        }

        if ($discussable instanceof Project) {
            return $discussable->parent_id ?: $discussable->getKey();
        }

        return null;
    }
}
