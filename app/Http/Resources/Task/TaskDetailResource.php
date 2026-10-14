<?php

namespace App\Http\Resources\Task;

use App\Http\Resources\Project\ProjectSummaryResource;
use App\Http\Resources\Shared\ApiDetailResource;
use App\Http\Resources\Shared\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class TaskDetailResource extends ApiDetailResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $task = $this->resource;
        $project = $task->project;
        $data = (new TaskResource($task))->resolve($request);

        $data['project'] = (new ProjectSummaryResource($project))->resolve($request);
        $data['assignees'] = $task->users
            ->map(function (User $user) use ($project, $request): array {
                $role = $project->userRole($user);

                return [
                    ...(new UserResource($user))->resolve($request),
                    'project_role' => $role ? [
                        'value' => $role->value,
                        'label' => $role->label(),
                    ] : null,
                ];
            })
            ->values()
            ->all();

        return array_merge($data, $this->visibleDetail($request));
    }
}
