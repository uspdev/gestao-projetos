<?php

namespace App\Services\Tasks;

use App\Enums\ProjectRequestStatus;
use App\Enums\Task\TaskStatus;
use App\Exceptions\ProjectRequestAlreadyEvaluatedException;
use App\Mail\TaskAssigned;
use App\Models\Project;
use App\Models\ProjectRequest;
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

    /**
     * Cria uma Tarefa pelo fluxo canônico e, quando informado, aceita a
     * Solicitação de origem dentro da mesma transação.
     *
     * @param array<string, mixed> $data
     */
    public function create(
        Project $project,
        User $creator,
        array $data,
        ?ProjectRequest $projectRequest = null,
        ?string $response = null,
    ): Task {
        [$task, $assignee] = DB::transaction(function () use (
            $project,
            $creator,
            $data,
            $projectRequest,
            $response,
        ): array {
            $lockedRequest = $projectRequest
                ? $this->lockPendingRequest($project, $projectRequest)
                : null;
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

            if ($lockedRequest) {
                $lockedRequest->forceFill([
                    'status' => ProjectRequestStatus::ACCEPTED,
                    'response' => $response,
                    'evaluated_by' => $creator->id,
                    'evaluated_at' => now(),
                    'task_id' => $task->id,
                ])->save();
            }

            return [$task, $assignee];
        });

        if ($assignee && $assignee->id !== $creator->id) {
            Mail::to($assignee->email)->queue(new TaskAssigned($assignee, $creator, $task));
        }

        return $task;
    }

    private function lockPendingRequest(Project $project, ProjectRequest $projectRequest): ProjectRequest
    {
        $lockedRequest = ProjectRequest::query()
            ->whereKey($projectRequest->getKey())
            ->where('project_id', $project->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedRequest->status !== ProjectRequestStatus::PENDING) {
            throw new ProjectRequestAlreadyEvaluatedException();
        }

        return $lockedRequest;
    }

    private function assignUser(Task $task, User $user): void
    {
        $task->users()->syncWithoutDetaching([$user->id]);

        if ($task->status === TaskStatus::NEW) {
            $task->update(['status' => TaskStatus::ASSIGNED]);
        }
    }
}
