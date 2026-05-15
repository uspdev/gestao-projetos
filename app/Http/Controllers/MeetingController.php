<?php

namespace App\Http\Controllers;

use App\Enums\Meeting\MeetingStatus;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Requests\MeetingItem\StoreMeetingItemRequest;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\MeetingItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        })->only(['index', 'create', 'show', 'edit']);
    }

    public function index(Project $project)
    {
        Gate::authorize('viewAny', [Meeting::class, $project]);

        $meetings = $project->meetings()
            ->with('projects')
            ->orderBy('scheduled_at')
            ->get();

        return view('projects.meetings.index', compact('project', 'meetings'));
    }

    public function create(Project $project)
    {
        Gate::authorize('create', [Meeting::class, $project]);

        $user = Auth::user();

        $availableProjects = Project::availableForMeetings($user)
            ->get()
            ->values();

        if ($availableProjects->where('id', $project->id)->isEmpty()) {
            $availableProjects->prepend($project);
        }
        $selectedProjects = old('projects', [$project->id]);

        return view('projects.meetings.create', compact('project', 'availableProjects', 'selectedProjects'));
    }

    public function store(StoreMeetingRequest $request, Project $project)
    {
        $meeting = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $projects = $data['projects'] ?? [];
            unset($data['projects']);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['status'] = $data['status'] ?? MeetingStatus::SCHEDULED->value;

            $meeting = Meeting::create($data);
            $meeting->projects()->sync($projects);

            return $meeting;
        });

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Reuniao criada com sucesso!');
    }

    public function show(Project $project, Meeting $meeting)
    {
        Gate::authorize('view', [$meeting, $project]);

        $meetingItems = $meeting->meetingItems()
            ->with('discussable')
            ->orderBy('order')
            ->get();

        $meetingProjects = $meeting->projects()
            ->with(['tasks', 'children'])
            ->get();

        $meeting->setRelation('projects', $meetingProjects);

        return view('projects.meetings.show', compact('project', 'meeting', 'meetingItems', 'meetingProjects'));
    }

    public function edit(Project $project, Meeting $meeting)
    {
        Gate::authorize('update', [$meeting, $project]);

        $meetingItems = $meeting->meetingItems()
            ->with('discussable')
            ->orderBy('order')
            ->get();

        $meetingProjects = $meeting->projects()
            ->with(['tasks', 'children'])
            ->get();

        $meeting->setRelation('projects', $meetingProjects);

        $user = Auth::user();

        $availableProjects = Project::availableForMeetings($user)
            ->get()
            ->values();

        if ($availableProjects->where('id', $project->id)->isEmpty()) {
            $availableProjects->prepend($project);
        }
        $selectedProjects = old('projects', $meeting->projects->pluck('id')->all());

        return view('projects.meetings.edit', compact(
            'project',
            'meeting',
            'availableProjects',
            'selectedProjects',
            'meetingItems',
            'meetingProjects'
        ));
    }

    public function update(UpdateMeetingRequest $request, Project $project, Meeting $meeting)
    {
        Gate::authorize('update', [$meeting, $project]);

        DB::transaction(function () use ($request, $meeting) {
            $data = $request->validated();
            $projects = $data['projects'] ?? [];
            unset($data['projects']);

            $data['updated_by'] = Auth::id();

            $meeting->update($data);
            $meeting->projects()->sync($projects);
        });

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Reuniao atualizada com sucesso!');
    }

    public function destroy(Project $project, Meeting $meeting)
    {
        Gate::authorize('delete', [$meeting, $project]);

        DB::transaction(function () use ($meeting) {
            $meeting->delete();
        });

        return redirect()->route('projects.meetings.index', $project)
            ->with('alert-success', 'Reuniao removida com sucesso!');
    }

    public function storeItem(StoreMeetingItemRequest $request, Project $project, Meeting $meeting)
    {
        $discussable = $request->discussable();

        abort_unless($discussable, 404);

        DB::transaction(function () use ($request, $meeting, $discussable) {
            $data = $request->validated();

            MeetingItem::create([
                'meeting_id' => $meeting->id,
                'discussable_type' => $discussable::class,
                'discussable_id' => $discussable->getKey(),
                'order' => $data['order'],
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Item de pauta adicionado com sucesso!');
    }
}
