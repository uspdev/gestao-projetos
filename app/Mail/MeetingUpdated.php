<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Meeting $meeting,
        public bool $isCancelled = false
    ) {}

    public function build(): self
    {
        $action = $this->isCancelled ? 'reunião cancelada' : 'reunião atualizada';

        return $this->subject(sprintf('[%s - %s] %s', $this->appName(), $this->projectName(), $action))
            ->view('emails.meeting.meeting-updated');
    }

    private function appName(): string
    {
        return config('app.name');
    }

    private function projectName(): string
    {
        $this->meeting->loadMissing('projects');

        return $this->meeting->projects->first()?->name ?? 'Projeto';
    }
}
