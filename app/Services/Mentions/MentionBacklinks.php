<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Mention;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Reúne as origens navegáveis de Menções recebidas por qualquer entidade.
 *
 * A interface deliberadamente recebe somente o destino e o leitor. A resolução
 * de fontes polimórficas, campos editoriais, permissões e rotas contextuais fica
 * concentrada aqui para que as telas não repitam essas decisões.
 */
class MentionBacklinks
{
    public function __construct(private MentionManager $mentionManager)
    {
    }

    /**
     * @return Collection<int, array{
     *     label: string,
     *     type: string,
     *     field: string,
     *     url: string,
     *     group_key: string,
     *     group_label: string,
     *     group_type: string,
     *     group_url: string,
     * }>
     */
    public function for(Model $target, ?User $reader = null): Collection
    {
        $reader ??= Auth::user();

        if (! $reader || ! Schema::hasTable('mentions')) {
            return collect();
        }

        return $this->mentionManager
            ->incomingMentions($target, $reader)
            // O índice de Menções não possui timestamps próprios; a última
            // atualização da fonte é a referência disponível para a recência.
            ->sortByDesc(fn (Mention $mention): int => $mention->source?->updated_at?->getTimestamp() ?? 0)
            ->map(fn (Mention $mention): ?array => $this->present($mention, $reader))
            ->filter()
            ->values();
    }

    /**
     * @return array{
     *     label: string,
     *     type: string,
     *     field: string,
     *     url: string,
     *     group_key: string,
     *     group_label: string,
     *     group_type: string,
     *     group_url: string,
     * }|null
     */
    private function present(Mention $mention, User $reader): ?array
    {
        $source = $mention->source;

        if (! $source instanceof Model) {
            return null;
        }

        $field = match ($mention->source_field) {
            'text' => 'Comentário',
            'notes' => $source instanceof MeetingItem
                ? 'Anotações prévias do item'
                : 'Anotações prévias',
            'description' => 'Descrição',
            default => $mention->source_field,
        };

        return match (true) {
            $source instanceof Project => $this->entry(
                label: $source->name,
                type: 'Projeto',
                field: $field,
                url: route('projects.show', $source),
                groupKey: 'project:' . $source->getKey(),
                groupLabel: $source->name,
                groupType: 'Projeto',
            ),
            $source instanceof Task => $this->task($source, $field),
            $source instanceof Meeting => $this->meeting($source, $reader, $field),
            $source instanceof MeetingItem => $this->meetingItem($source, $reader, $field),
            $source instanceof Comment => $this->comment($source, $reader, $field),
            default => null,
        };
    }

    /**
     * @return array{
     *     label: string,
     *     type: string,
     *     field: string,
     *     url: string,
     *     group_key: string,
     *     group_label: string,
     *     group_type: string,
     *     group_url: string,
     * }|null
     */
    private function task(Task $task, string $field): ?array
    {
        if (! $task->project) {
            return null;
        }

        return $this->entry(
            label: $task->title,
            type: 'Tarefa',
            field: $field,
            url: route('tasks.show', $task),
            groupKey: 'task:' . $task->getKey(),
            groupLabel: $task->title,
            groupType: 'Tarefa',
        );
    }

    /**
     * @return array{
     *     label: string,
     *     type: string,
     *     field: string,
     *     url: string,
     *     group_key: string,
     *     group_label: string,
     *     group_type: string,
     *     group_url: string,
     * }|null
     */
    private function meeting(Meeting $meeting, User $reader, string $field): ?array
    {
        $contextProject = $meeting->contextProjectFor($reader);

        if (! $contextProject) {
            return null;
        }

        return $this->entry(
            label: $meeting->title,
            type: 'Reunião',
            field: $field,
            url: route('projects.meetings.show', [$contextProject, $meeting]),
            groupKey: 'meeting:' . $meeting->getKey(),
            groupLabel: $meeting->title,
            groupType: 'Reunião',
        );
    }

    /**
     * @return array{
     *     label: string,
     *     type: string,
     *     field: string,
     *     url: string,
     *     group_key: string,
     *     group_label: string,
     *     group_type: string,
     *     group_url: string,
     * }|null
     */
    private function meetingItem(MeetingItem $item, User $reader, string $field): ?array
    {
        $meeting = $item->meeting;
        $contextProject = $meeting?->contextProjectFor($reader);

        if (! $meeting || ! $contextProject) {
            return null;
        }

        $label = $item->discussable?->title
            ?? $item->discussable?->name
            ?? $item->title
            ?? "Item #{$item->order}";

        return $this->entry(
            label: $label,
            type: 'Item de pauta em ' . $meeting->title,
            field: $field,
            url: route('projects.meetings.show', [$contextProject, $meeting]),
            groupKey: 'meeting_item:' . $item->getKey(),
            groupLabel: $label,
            groupType: 'Item de pauta',
        );
    }

    /**
     * @return array{label: string, type: string, field: string, url: string}|null
     */
    private function comment(Comment $comment, User $reader, string $field): ?array
    {
        $commentable = $comment->commentable;

        return match (true) {
            $commentable instanceof Project => $this->entry(
                label: $commentable->name,
                type: 'Comentário em projeto',
                field: $field,
                url: route('projects.show', $commentable),
                groupKey: 'project:' . $commentable->getKey(),
                groupLabel: $commentable->name,
                groupType: 'Projeto',
            ),
            $commentable instanceof Task => $commentable->project ? $this->entry(
                label: $commentable->title,
                type: 'Comentário em tarefa',
                field: $field,
                url: route('tasks.show', $commentable),
                groupKey: 'task:' . $commentable->getKey(),
                groupLabel: $commentable->title,
                groupType: 'Tarefa',
            ) : null,
            $commentable instanceof Meeting => $this->meeting($commentable, $reader, $field),
            default => null,
        };
    }

    /**
     * @return array{
     *     label: string,
     *     type: string,
     *     field: string,
     *     url: string,
     *     group_key: string,
     *     group_label: string,
     *     group_type: string,
     *     group_url: string,
     * }
     */
    private function entry(
        string $label,
        string $type,
        string $field,
        string $url,
        string $groupKey,
        string $groupLabel,
        string $groupType,
    ): array {
        return [
            'label' => $label,
            'type' => $type,
            'field' => $field,
            'url' => $url,
            'group_key' => $groupKey,
            'group_label' => $groupLabel,
            'group_type' => $groupType,
            'group_url' => $url,
        ];
    }
}
