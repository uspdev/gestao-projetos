<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Project;

class UserTaskController extends Controller
{
    /**
     * Construtor do controlador.
     * Configura o middleware para ativar a URL no tema.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('minhas-tasks');

            return $next($request);
        });
    }
    public function index()
    {
        $user = Auth::user();
        Gate::authorize('viewTasks', $user);
        $showDone = request()->boolean('show_done');
        $tasks = $user->tasks()
            ->with([
                'project:id,name,status',
                'users:id,name',
            ])
            ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy(
                Project::select('name')
                    ->whereColumn('projects.id', 'tasks.project_id')
            )
            ->latest()
            ->get();

        return view('user-tasks.index', compact('tasks', 'user'));
    }
}
