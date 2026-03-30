<?php

namespace App\Http\Controllers;

use App\Actions\User\IndexUserTaskAction;
use App\Http\Requests\User\IndexUserTaskRequest;
use App\Models\User;

class UserTaskController extends Controller
{
    public function index(IndexUserTaskRequest $request, User $user, IndexUserTaskAction $action)
    {
        $tasks = $action->execute($user);

        return view('User.Task.index', compact('tasks', 'user'));
    }
}