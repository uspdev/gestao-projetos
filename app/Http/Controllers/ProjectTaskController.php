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
        $showDone = request()->boolean('show_done');
        
        $tasks = $project->tasks()
            ->with('project:id,name,status')
            ->with('users:id,name')
            ->with('tags:id,name,color,description')
            ->when(! $showDone, fn($query) => $query->where('status', '!=', TaskStatus::DONE->value))
            ->orderBy('priority', 'asc')
            ->latest()
            ->get();

        $availableTags = Tag::withType('projects')
            ->select('id', 'name', 'color', 'description')
            ->orderBy('name')
            ->get();

        $projectSelectedTags = collect(old('tags', $project->tagsWithType('projects')->pluck('id')->all()))
            ->map(fn($id) => (int) $id)
            ->all();

        $availableTaskTags = Tag::withType('tasks')
            ->select('id', 'name', 'color', 'description')
            ->orderBy('name')
            ->get();

        $tasksSelectedTags = $tasks->mapWithKeys(function ($task) {
            return [$task->id => $task->tags->pluck('id')->all()];
        });

        return view('project-tasks.index', compact(
            'tasks',
            'project',
            'availableTags',
            'projectSelectedTags',
            'availableTaskTags',
            'tasksSelectedTags'
        ));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Task::class, $project]);

        $availableTags = Tag::withType('tasks')
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
                $tagsToSync = Tag::whereIn('id', $request->tags)->get();
                $task->syncTagsWithType($tagsToSync, 'tasks');
            }

            return $task;
        });

        return redirect()->route('tasks.show', $task)
            ->with('alert-success', 'Tarefa criada com sucesso!');
    }
}