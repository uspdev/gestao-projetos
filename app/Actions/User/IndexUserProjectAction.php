<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class IndexUserProjectAction
{
    public function execute(User $user): Collection
    {
        return $user->projects()
                    ->with([
                        'users:id,name',
                        'tasks:id,project_id,status',
                    ])
                    ->latest()
                    ->get();
    }
}