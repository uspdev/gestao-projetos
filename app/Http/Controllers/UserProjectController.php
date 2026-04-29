<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
    public function index()
    {
        $user = Auth::user();
        Gate::authorize('viewAny', [Project::class, $user]);
        $projects = $user->projects()
            ->with([
                'users:id,name',
                'tasks:id,project_id,status',
            ])
            ->latest()
            ->get();

        $availableTags = Tag::withType('projects')
            ->select('id', 'name', 'color')
            ->orderBy('name')
            ->get();

        return view('user-projects.index', compact('projects', 'user', 'availableTags'));
    }
}
