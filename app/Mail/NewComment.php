<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewComment extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $contextLabel;
    public string $contextName;
    public string $actionLabel;
    public ?string $actionUrl;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Comment $comment,
        public Model $commentable
    ) {
        $this->contextLabel = $this->resolveContextLabel();
        $this->contextName = $this->resolveContextName();
        $this->actionLabel = $this->resolveActionLabel();
        $this->actionUrl = $this->resolveActionUrl();
    }

    public function build(): self
    {
        return $this->subject(sprintf('%s | novo comentário', $this->projectName()))
            ->view('emails.comment.new-comment');
    }

    /**
     * Obtém o nome do projeto relacionado ao comentário.
     */
    private function projectName(): string
    {
        if ($this->commentable instanceof Project) {
            return $this->commentable->name;
        }

        if ($this->commentable instanceof Task) {
            $this->commentable->loadMissing('project');

            return $this->commentable->project?->name ?? 'Projeto';
        }

        if ($this->commentable instanceof Meeting) {
            $this->commentable->loadMissing('projects');

            return $this->commentable->projects->first()?->name ?? 'Projeto';
        }

        if ($this->commentable instanceof MeetingItem) {
            $this->commentable->loadMissing('meeting.projects');

            return $this->commentable->meeting?->projects->first()?->name ?? 'Projeto';
        }

        return 'Projeto';
    }

    /**
     * Obtém o label do contexto do comentário.
     */
    private function resolveContextLabel(): string
    {
        return match (true) {
            $this->commentable instanceof Project => 'Projeto',
            $this->commentable instanceof Task => 'Task',
            $this->commentable instanceof Meeting => 'Reunião',
            $this->commentable instanceof MeetingItem => 'Item de pauta',
            default => 'Recurso',
        };
    }

    /**
     * Obtém o nome do contexto do comentário.
     */
    private function resolveContextName(): string
    {
        return match (true) {
            $this->commentable instanceof Project => $this->commentable->name,
            $this->commentable instanceof MeetingItem => $this->commentable->discussable?->title
                ?? $this->commentable->discussable?->name
                ?? ('Item #' . $this->commentable->order),
            default => $this->commentable->title ?? ('#' . $this->commentable->id),
        };
    }

    /**
     * Obtém o label da ação do comentário.
     */
    private function resolveActionLabel(): string
    {
        return match (true) {
            $this->commentable instanceof Project => 'Ver projeto',
            $this->commentable instanceof Task => 'Ver tarefa',
            $this->commentable instanceof Meeting,
            $this->commentable instanceof MeetingItem => 'Ver reunião',
            default => 'Ver detalhes',
        };
    }

    /**
     * Obtém a URL da ação do comentário.
     */
    private function resolveActionUrl(): ?string
    {
        return match (true) {
            $this->commentable instanceof Project => route('projects.show', $this->commentable),

            $this->commentable instanceof Task => route('tasks.show', $this->commentable),

            $this->commentable instanceof Meeting => $this->meetingUrl($this->commentable),

            $this->commentable instanceof MeetingItem => $this->meetingUrl($this->commentable->meeting),

            default => null,
        };
    }

    /**
     * Obtém a URL da reunião.
     */
    private function meetingUrl(?Meeting $meeting): ?string
    {
        if (! $meeting) {
            return null;
        }

        $meeting->loadMissing('projects');

        $project = $meeting->projects->first();

        return $project
            ? route('projects.meetings.show', [$project, $meeting])
            : null;
    }
}
