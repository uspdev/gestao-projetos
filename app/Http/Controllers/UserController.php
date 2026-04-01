<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function show(User $user)
    {
        Gate::authorize('view', $user);
        $user = $user->load([
            'roles:id,name',
            'projects:id,name,status',
            'tasks:id,project_id,title,status,due_date',
        ]);

        return view('User.show', compact('user'));
    }
}