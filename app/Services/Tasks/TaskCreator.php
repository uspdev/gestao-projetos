<?php

namespace App\Services\Tasks;

use App\Enums\Task\TaskStatus;
use App\Mail\TaskAssigned;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\Mentions\MentionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class TaskCreator
{
    public function __construct(private MentionManager $mentionManager)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(
        Project $project,
        User $creator,
        array $data,
    ): Task {
        [$task, $assignee] = DB::transaction(function () use (
            $project,
            $creator,
            $data,
        ): array {
            $assigneeId = $data['assignee_id'] ?? null;
            $tagIds = $data['tags'] ?? [];
            unset($data['assignee_id'], $data['tags']);

            $data['project_id'] = $project->id;
            $data['status'] = $data['status'] ?? TaskStatus::ASSIGNED->value;

            $task = new Task($data);
            $task->forceFill([
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);
            $task->save();

            $assignee = $assigneeId ? User::query()->findOrFail($assigneeId) : null;

            if ($assignee) {
                $this->assignUser($task, $assignee);
            }

            $description = $data['description'] ?? null;
            $this->mentionManager->validateAllMentions($task, 'description', $description, $creator);
            $this->mentionManager->synchronize($task, 'description', $description, actor: $creator);

            if ($tagIds !== []) {
                $tags = Tag::withType(Tag::TYPE_TASK)
                    ->whereIn('id', $tagIds)
                    ->get();
                $task->syncTagsWithType($tags, Tag::TYPE_TASK);
            }

            return [$task, $assignee];
        });

        if ($assignee && $assignee->id !== $creator->id) {
            Mail::to($assignee->email)->queue(new TaskAssigned($assignee, $creator, $task));
        }

        return $task;
    }

    private function assignUser(Task $task, User $user): void
    {
        $task->users()->syncWithoutDetaching([$user->id]);

        if ($task->status === TaskStatus::NEW) {
            $task->update(['status' => TaskStatus::ASSIGNED]);
        }
    }
}
