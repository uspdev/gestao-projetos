<?php

namespace App\Http\Controllers;

use App\Actions\User\IndexUserTaskAction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Models\Task;

class UserTaskController extends Controller
{
    public function index(User $user, IndexUserTaskAction $action)
    {
        Gate::authorize('viewAny', [Task::class, $user]);
        $tasks = $action->execute($user);

        return view('User.Task.index', compact('tasks', 'user'));
    }
}