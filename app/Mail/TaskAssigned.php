<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Task $task
    ) {}

    public function build(): self
    {
        $project = $this->task->project;

        return $this->subject(sprintf('[%s - %s] tarefa atribuída', config('app.name'), $this->task->project->name))
            ->with([
                'projectRole' => $project->userRole($this->recipient)?->label(),
            ])
            ->view('emails.task.task-assigned');
    }
}
