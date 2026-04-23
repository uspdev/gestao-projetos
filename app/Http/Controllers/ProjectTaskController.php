<?php

namespace App\Http\Controllers;

use App\Enums\Task\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Models\Project; 
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\Tag;

class ProjectTaskController extends Controller
{
    public function index(Project $project)
    {
        Gate::authorize('viewAny', [Task::class, $project]);
        $tasks = $project->tasks()
                         ->with('project:id,name,status')
                         ->with('users:id,name')
                         ->with('tags:id,name,color,description')
                         ->orderBy('priority', 'asc')
                         ->latest()
                         ->get();
        
        return view('project-tasks.index', compact('tasks', 'project'));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Task::class, $project]);

        $availableTags = Tag::getWithType('tasks')
                            ->select('id', 'name', 'color')
                            ->orderBy('name')
                            ->get();

        return view('project-tasks.create', compact('project', 'availableTags'));
    }

    public function store(StoreTaskRequest $request, Project $project)
    {
        $task = DB::transaction(function () use ($project, $request) {
            $data = $request->validated();
            $data['project_id'] = $project->id;
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? TaskStatus::TO_DO->value;

            $task = Task::create($data);
            $task->users()->attach(Auth::id());

            if ($request->has('tags')) {
                $task->syncTagsWithType($request->tags, 'tasks');
            }

            return $task;
        });

        return redirect()->route('tasks.show', $task)
                         ->with('success', 'Tarefa criada com sucesso!');
    }
}