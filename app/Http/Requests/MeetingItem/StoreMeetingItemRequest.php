<?php

namespace App\Http\Requests\MeetingItem;

use App\Morphs\Discussable;
use App\Models\Meeting;
use App\Models\Project;
use App\Morphs\DiscussableMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use App\Enums\Meeting\MeetingStatus;

class StoreMeetingItemRequest extends FormRequest
{
    public const INDEPENDENT_TYPE = 'independent';

    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge(['title' => trim((string) $this->input('title'))]);
        }
    }

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

        if ($this->isIndependent()) {
            return true;
        }

        $discussable = $this->discussable();

        if (!$discussable) {
            abort(404, 'O item (projeto ou tarefa) que você está tentando vincular não foi encontrado.');
        }

        $linkedProjectIds = $this->resolveLinkedProjectIds($discussable);
        if ($linkedProjectIds === []) {
            throw new AuthorizationException('O item de pauta é inválido.');
        }

        if (!$meeting->projects()->whereIn('projects.id', $linkedProjectIds)->exists()) {
            throw new AuthorizationException('Este item pertence a um projeto que não está vinculado a esta reunião.');
        }

        return true;
    }

    public function rules(): array
    {
        $meeting = $this->route('meeting');

        $typeInput = (string) $this->input('item_type', '');
        $discussableClass = DiscussableMap::resolveClass($typeInput);

        $discussableType = $discussableClass
            ? (new $discussableClass)->getMorphClass()
            : $typeInput;

        $allowedTypes = array_merge(DiscussableMap::allowedValues(), [self::INDEPENDENT_TYPE]);

        return [
            'item_type'        => ['required', 'string', Rule::in($allowedTypes)],
            'order'            => ['required', 'integer', 'min:1'],

            'discussable_id'   => $this->isIndependent()
                ? ['nullable']
                : [
                    'required',
                    'integer',
                    Rule::unique('meeting_items')->where(function ($query) use ($meeting, $discussableType) {
                        return $query->where('meeting_id', $meeting->id)
                            ->where('discussable_type', $discussableType);
                    }),
                ],
            'title' => $this->isIndependent()
                ? ['required', 'string', 'min:3', 'max:255']
                : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_type.required'        => 'Informe o tipo do item de pauta.',
            'item_type.string'          => 'O tipo do item de pauta é inválido.',
            'item_type.in'              => 'O tipo do item de pauta é inválido.',
            'discussable_id.required'   => 'Informe o item de pauta.',
            'discussable_id.integer'    => 'O item de pauta é inválido.',
            'discussable_id.unique'     => 'Este item já foi adicionado à pauta desta reunião.',
            'order.required'            => 'Informe a ordem do item.',
            'order.integer'             => 'A ordem do item é inválida.',
            'order.min'                 => 'A ordem do item deve ser maior ou igual a :min.',
            'title.required'            => 'Informe o título do item independente.',
            'title.string'              => 'O título do item independente é inválido.',
            'title.min'                 => 'O título do item independente deve ter pelo menos :min caracteres.',
            'title.max'                 => 'O título do item independente não pode exceder :max caracteres.',
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

            if ($this->isIndependent()) {
                return;
            }

            $discussable = $this->discussable();
            if (!$discussable) {
                $validator->errors()->add('discussable_id', 'O item de pauta não foi encontrado.');
                return;
            }

            $linkedProjectIds = $this->resolveLinkedProjectIds($discussable);
            if ($linkedProjectIds === []) {
                $validator->errors()->add('discussable_id', 'O item de pauta é inválido.');
                return;
            }

            $linked = $meeting->projects()
                ->whereIn('projects.id', $linkedProjectIds)
                ->exists();

            if (!$linked) {
                $validator->errors()->add(
                    'discussable_id',
                    'A reunião não está vinculada ao projeto pai deste item.'
                );
            }
        });
    }

    public function discussable(): ?Model
    {
        $type = (string) $this->input('item_type', '');
        $id = $this->input('discussable_id');

        if ($type === '' || !$id) {
            return null;
        }

        $class = DiscussableMap::resolveClass($type);
        if (!$class) {
            return null;
        }

        return $class::query()->find($id);
    }

    public function isIndependent(): bool
    {
        return (string) $this->input('item_type') === self::INDEPENDENT_TYPE;
    }

    /**
    *Esta função resolve os IDs dos projetos relacionados ao item de pauta, considerando a hierarquia de projetos.
    */
    private function resolveLinkedProjectIds(Model $discussable): array
    {
        if (!$discussable instanceof Discussable) {
            return [];
        }

        $projectId = $discussable->parentProjectId();

        if (!$projectId) {
            return [];
        }

        if ($discussable instanceof Project && $discussable->parent_id) {
            return array_values(array_unique([$discussable->id, $projectId]));
        }

        return [$projectId];
    }
}
