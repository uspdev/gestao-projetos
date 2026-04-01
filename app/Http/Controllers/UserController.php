<?php

namespace App\Http\Controllers;

use App\Actions\User\ShowUserProfileAction;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, User $user, ShowUserProfileAction $action)
    {
        $user = $action->execute($user);

        return view('User.show', compact('user'));
    }
}