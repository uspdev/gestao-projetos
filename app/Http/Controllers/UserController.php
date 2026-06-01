<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meu-perfil');
            return $next($request);
        })->only(['show']);
    }

    public function show(User $user, Request $request)
    {
        Gate::authorize('view', $user);

        $taskViewDefault = 'kanban';  //list ou kanban
        $taskView = request()->query('view') ?? session('tasks_view', $taskViewDefault);
        session(['tasks_view' => $taskView]);

        $taskDoneDefault = 0; // 0 não exibe, ou 1 exibe tarefas concluídas
        $tasksDone = $request->query('tasks_done') ?? session('tasks_done', $taskDoneDefault);
        session(['tasks_done' => $tasksDone]);

        $user->load([
            'roles',
            'projects',
            'tasks' => fn($query) => $query->withTasksModuleEnabled(),
            'tasks.project',
            'tasks.tags',
        ]);

        if ($user->projects->isEmpty()) {
            return view('projects.index-no-project');
        }

        $tasksByStatus = $user->tasksByStatus($taskView, $tasksDone);

        return view('users.show', compact('user', 'tasksByStatus'));
    }
}
