<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $relations = [
            'roles',
            'projects',
        ];

        // Apenas o usuário logado pode ver suas próprias tasks
        if (Auth::id() === $user->id) {
            $relations = array_merge($relations, [
                'tasks' => fn($query) => $query->withTasksModuleEnabled(),
                'tasks.project',
                'tasks.tags',
            ]);
        }

        $user = $user->load($relations);

        return view('users.show', compact('user'));
    }
}
