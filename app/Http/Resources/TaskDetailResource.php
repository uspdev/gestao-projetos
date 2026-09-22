<?php

namespace App\Http\Resources;

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

        $data['project'] = [
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
            'web_url' => route('projects.show', $project),
        ];
        $data['assignees'] = $task->users
            ->map(function (User $user) use ($project): array {
                $role = $project->userRole($user);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'project_role' => $role ? [
                        'value' => $role->value,
                        'label' => $role->label(),
                    ] : null,
                    'web_url' => route('users.show', $user),
                ];
            })
            ->values()
            ->all();

        return array_merge($data, $this->visibleDetail($request));
    }
}
