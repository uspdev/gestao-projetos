<?php

namespace App\Http\Controllers;

use App\Actions\User\IndexUserProjectAction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Models\Project;

class UserProjectController extends Controller
{
    public function index(User $user, IndexUserProjectAction $action)
    {
        Gate::authorize('viewAny', [Project::class, $user]);
        $projects = $action->execute($user);

        return view('User.Project.index', compact('projects', 'user'));
    }
}