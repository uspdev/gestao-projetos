<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingWatchUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Meeting $meeting,
        public string $summary,
    ) {}

    public function build(): self
    {
        return $this->subject(sprintf(
            '[%s] %s',
            config('app.name'),
            mb_strtolower(rtrim($this->summary, '.')),
        ))->view('emails.watch.meeting-update');
    }
}
