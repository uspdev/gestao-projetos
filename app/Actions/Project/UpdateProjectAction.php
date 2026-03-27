<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class UpdateProjectAction
{
    public function execute(Project $project, array $data, int $userId): Project
    {
        return DB::transaction(function () use ($project, $data, $userId) {
            $data['updated_by'] = $userId;

            $project->update($data);

            return $project->fresh();
        });
    }
}