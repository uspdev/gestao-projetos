<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectLinkedAsSubproject extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Project $parentProject,
        public Project $subproject
    ) {}

    public function build(): self
    {
        return $this->subject('Projeto vinculado como subprojeto: ' . $this->subproject->name)
            ->view('emails.project.subproject-linked');
    }
}
