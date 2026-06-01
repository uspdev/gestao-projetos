<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Meeting $meeting
    ) {}

    public function build(): self
    {
        return $this->subject('Reuniao criada: ' . $this->meeting->title)
            ->view('emails.meeting.meeting-created');
    }
}
