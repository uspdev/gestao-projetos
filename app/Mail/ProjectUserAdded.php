<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectUserAdded extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Project $project
    ) {}

    public function build(): self
    {
        return $this->subject(sprintf('[%s - %s] colaborador adicionado ao projeto', config('app.name'), $this->project->name))
            ->view('emails.project.added-to-project');
    }
}
