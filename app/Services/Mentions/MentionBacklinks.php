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
     * @return Collection<int, array{label: string, type: string, field: string, url: string}>
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
     * @return array{label: string, type: string, field: string, url: string}|null
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
            $source instanceof Project => [
                'label' => $source->name,
                'type' => 'Projeto',
                'field' => $field,
                'url' => route('projects.show', $source),
            ],
            $source instanceof Task => $this->task($source, $field),
            $source instanceof Meeting => $this->meeting($source, $reader, $field),
            $source instanceof MeetingItem => $this->meetingItem($source, $reader, $field),
            $source instanceof Comment => $this->comment($source, $reader, $field),
            default => null,
        };
    }

    /**
     * @return array{label: string, type: string, field: string, url: string}|null
     */
    private function task(Task $task, string $field): ?array
    {
        return $task->project ? [
            'label' => $task->title,
            'type' => 'Tarefa',
            'field' => $field,
            'url' => route('tasks.show', $task),
        ] : null;
    }

    /**
     * @return array{label: string, type: string, field: string, url: string}|null
     */
    private function meeting(Meeting $meeting, User $reader, string $field): ?array
    {
        $contextProject = $meeting->contextProjectFor($reader);

        return $contextProject ? [
            'label' => $meeting->title,
            'type' => 'Reunião',
            'field' => $field,
            'url' => route('projects.meetings.show', [$contextProject, $meeting]),
        ] : null;
    }

    /**
     * @return array{label: string, type: string, field: string, url: string}|null
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

        return [
            'label' => $label,
            'type' => 'Item de pauta em ' . $meeting->title,
            'field' => $field,
            'url' => route('projects.meetings.show', [$contextProject, $meeting]),
        ];
    }

    /**
     * @return array{label: string, type: string, field: string, url: string}|null
     */
    private function comment(Comment $comment, User $reader, string $field): ?array
    {
        $commentable = $comment->commentable;

        return match (true) {
            $commentable instanceof Project => [
                'label' => $commentable->name,
                'type' => 'Comentário em projeto',
                'field' => $field,
                'url' => route('projects.show', $commentable),
            ],
            $commentable instanceof Task => $commentable->project ? [
                'label' => $commentable->title,
                'type' => 'Comentário em tarefa',
                'field' => $field,
                'url' => route('tasks.show', $commentable),
            ] : null,
            $commentable instanceof Meeting => $this->meeting($commentable, $reader, $field),
            default => null,
        };
    }
}
