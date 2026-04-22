<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $user = $user->load([
            'roles:id,name',
            'projects:id,name,status',
            'tasks:id,project_id,title,priority,status,label,start_date,due_date',
        ]);

        return view('users.show', compact('user'));
    }
}
