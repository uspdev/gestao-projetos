<?php

namespace App\Http\Resources;

use App\Enums\ProjectRequestStatus;
use App\Models\ProjectRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProjectRequest $projectRequest */
        $projectRequest = $this->resource;

        return [
            'id' => $projectRequest->id,
            'title' => $projectRequest->title,
            'description' => $projectRequest->description,
            'source_url' => $projectRequest->source_url,
            'status' => [
                'value' => $projectRequest->status->value,
                'label' => $projectRequest->status->label(),
            ],
            'response' => $projectRequest->response,
            'created_at' => $projectRequest->created_at?->toISOString(),
            'updated_at' => $projectRequest->updated_at?->toISOString(),
            'evaluated_at' => $projectRequest->evaluated_at?->toISOString(),
            'task' => $this->taskSummary($projectRequest),
            'web_url' => route('projects.requests.show', [$projectRequest->project, $projectRequest]),
        ];
    }

    /**
     * @return array{id: int, title: string, web_url: string}|null
     */
    private function taskSummary(ProjectRequest $projectRequest): ?array
    {
        $task = $projectRequest->task;

        if ($projectRequest->status !== ProjectRequestStatus::ACCEPTED || ! $task) {
            return null;
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'web_url' => route('tasks.show', $task),
        ];
    }
}
