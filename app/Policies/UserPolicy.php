<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $authUser, User $user): bool
    {
        return true;
    }

    public function viewTasks(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id;
    }
}
