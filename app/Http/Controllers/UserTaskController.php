<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Models\Task;
use App\Models\Project;

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
                      ->orderBy(
                        Project::select('name')
                               ->whereColumn('projects.id', 'tasks.project_id')
                      )
                      ->latest()
                      ->get();

        return view('user-tasks.index', compact('tasks', 'user'));
    }
}