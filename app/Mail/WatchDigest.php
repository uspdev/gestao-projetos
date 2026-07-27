<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WatchDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public Collection $notifications,
    ) {
    }

    public function build(): self
    {
        return $this->subject(sprintf(
            '[%s] resumo de atividades (%d)',
            config('app.name'),
            $this->notifications->count(),
        ))->view('emails.watch.digest');
    }
}
