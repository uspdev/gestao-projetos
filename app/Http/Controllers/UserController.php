<?php

namespace App\Http\Controllers;

use App\Actions\User\ShowUserProfileAction;
use App\Http\Requests\User\ShowUserProfileRequest;
use App\Models\User;

class UserController extends Controller
{
    public function show(ShowUserProfileRequest $request, User $user, ShowUserProfileAction $action)
    {
        $user = $action->execute($user);

        return view('User.show', compact('user'));
    }
}