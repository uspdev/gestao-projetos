<?php

namespace App\Http\Requests\Project;

use App\Models\Project;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('update', $project);
    }
}
