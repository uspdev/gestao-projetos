<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\Shared\UserResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMemberResource extends JsonResource
{
    public function __construct(User $user, private Project $project)
    {
        parent::__construct($user);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $role = $this->project->userRole($user);

        return [
            ...(new UserResource($user))->resolve($request),
            'role' => $role ? [
                'value' => $role->value,
                'label' => $role->label(),
            ] : null,
        ];
    }
}
