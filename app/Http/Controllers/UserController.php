<?php

namespace App\Http\Controllers;

use App\Actions\User\ShowUserProfileAction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function show(User $user, ShowUserProfileAction $action)
    {
        Gate::authorize('view', $user);
        $user = $action->execute($user);

        return view('User.show', compact('user'));
    }
}