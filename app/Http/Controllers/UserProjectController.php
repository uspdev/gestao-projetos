<?php

namespace App\Http\Controllers;

use App\Actions\User\IndexUserProjectAction;
use App\Http\Requests\User\IndexUserProjectRequest;
use App\Models\User;

class UserProjectController extends Controller
{
    public function index(IndexUserProjectRequest $request, User $user, IndexUserProjectAction $action)
    {
        $projects = $action->execute($user);

        return view('User.Project.index', compact('projects', 'user'));
    }
}