<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    public function __construct()
    {

        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('admin');
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        // return view('admin.card');

        $stats['projects'] = Project::count();
        $stats['tasks'] = Task::count();
        $stats['users'] = User::count();
        $stats['meetings'] = Meeting::count();

        $modules = Module::all();
        $projectTypes = ProjectType::orderBy('name')->get();
        $tags = Tag::all();

        $usersWithProjects = User::query()
            ->has('projects')
            ->with('projects')
            ->orderBy('users.name')
            ->get();

        return view('admin.index', compact('usersWithProjects', 'stats', 'modules', 'projectTypes', 'tags'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
