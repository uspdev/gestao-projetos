<?php

namespace App\Actions\User;

use App\Models\User;

class ShowUserProfileAction
{
    public function execute(User $user): User
    {
        return $user->load([
            'roles:id,name',
            'projects:id,name,status',
            'tasks:id,project_id,title,status,due_date',
        ]);
    }
}