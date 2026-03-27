<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DestroyProjectAction
{
    public function execute(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            return (bool) $project->delete();
        });
    }
}