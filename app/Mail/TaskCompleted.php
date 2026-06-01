<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $actor,
        public Task $task
    ) {}

    public function build(): self
    {
        return $this->subject('Tarefa concluida: ' . $this->task->title)
            ->view('emails.task.task-completed');
    }
}
