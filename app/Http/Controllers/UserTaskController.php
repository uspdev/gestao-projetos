<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Models\Task;

class UserTaskController extends Controller
{
    public function index(User $user)
    {
        Gate::authorize('viewAny', [Task::class, $user]);
        $tasks = $user->tasks()
                      ->with([
                          'project:id,name,status',
                          'users:id,name',
                      ])
                      ->orderBy('priority', 'asc')
                      ->latest()
                      ->get();

        return view('user-tasks.index', compact('tasks', 'user'));
    }
}