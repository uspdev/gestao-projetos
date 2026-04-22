<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Models\Project;

class UserProjectController extends Controller
{
    /**
     * Construtor do controlador.
     * Configura o middleware para ativar a URL no tema.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        });
    }
    public function index(User $user)
    {
        Gate::authorize('viewAny', [Project::class, $user]);
        $projects = $user->projects()
                         ->with([
                            'users:id,name',
                            'tasks:id,project_id,status',
                         ])
                         ->latest()
                         ->get();

        return view('user-projects.index', compact('projects', 'user'));
    }
}
