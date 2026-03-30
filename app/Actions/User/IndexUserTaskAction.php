<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class IndexUserTaskAction
{
    public function execute(User $user): Collection
    {
        return $user->tasks()
                    ->with([
                        'project:id,name,status',
                        'users:id,name',
                    ])
                    ->orderBy('priority', 'asc')
                    ->latest()
                    ->get();
    }
}