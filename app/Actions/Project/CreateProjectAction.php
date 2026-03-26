<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Support\Facades\DB;

class CreateProjectAction
{
    public function execute(array $data, int $userId): Project
    {
        // Envolvemos tudo em uma transação de banco de dados
        return DB::transaction(function () use ($data, $userId) {
            
            $data['created_by'] = $userId;            
            $data['status'] = $data['status'] ?? ProjectStatus::PLANNING->value;
            
            $project = Project::create($data);
            $project->users()->attach($userId);

            return $project;
        });
    }
}