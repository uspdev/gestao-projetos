<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Construtor do controlador.
     * Configura o middleware para ativar a URL no tema.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meu-perfil');

            return $next($request);
        });
    }
    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $relations = [
            'roles:id,name',
            'projects:id,name,slug,status',
        ];

        // Apenas o usuário logado pode ver suas próprias tasks
        if (Auth::id() === $user->id) {
            $relations = array_merge($relations, [
                'tasks:id,project_id,title,priority,status,start_date,due_date',
                'tasks.project:id,name,slug',
                'tasks.tags',
            ]);
        }

        $user = $user->load($relations);

        return view('users.show', compact('user'));
    }
}
