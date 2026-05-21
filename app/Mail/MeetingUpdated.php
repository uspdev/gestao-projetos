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
        $subjectPrefix = $this->isCancelled ? 'Reuniao cancelada: ' : 'Reuniao atualizada: ';

        return $this->subject($subjectPrefix . $this->meeting->title)
            ->view('emails.meeting.meeting-updated');
    }
}
