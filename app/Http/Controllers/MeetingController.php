<?php

namespace App\Http\Controllers;

use App\Enums\Meeting\MeetingStatus;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Requests\MeetingItem\StoreMeetingItemRequest;
use App\Http\Requests\Meeting\UpdateMeetingStatusRequest;
use App\Mail\MeetingCreated;
use App\Mail\MeetingUpdated;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\MeetingItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

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
            ->orderBy('status', 'desc')
            ->orderBy('scheduled_at')
            ->get();

        return view('module-meetings.index', compact('project', 'meetings'));
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

        return view('module-meetings.create', compact('project', 'availableProjects', 'selectedProjects'));
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
        // Lida com a notificação de reunião criada após a transaction
        // para evitar enviar emails caso haja falha na criação da reunião ou associação com os projetos
        $actor = Auth::user();
        $meeting->load('projects.users');
        // Envia notificacao para os usuarios dos projetos relacionados à reuniao, exceto para o proprio autor da acao
        $meeting->projects
            ->flatMap(fn(Project $project) => $project->users)
            ->unique('id')
            ->filter(fn($user) => !$actor || $user->id !== $actor->id)
            ->each(function ($user) use ($actor, $meeting) {
                Mail::to($user->email)->queue(new MeetingCreated($user, $actor, $meeting));
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

        $agendaData = $meeting->meetingItemFormData($meetingItems);
        $meetingProjects = $agendaData['meetingProjects'];

        $meeting->setRelation('projects', $meetingProjects);

        return view('module-meetings.show', array_merge(
            compact('project', 'meeting', 'meetingItems'),
            $agendaData
        ));
    }

    public function edit(Project $project, Meeting $meeting)
    {
        Gate::authorize('update', [$meeting, $project]);

        $meetingItems = $meeting->meetingItems()
            ->with('discussable')
            ->orderBy('order')
            ->get();

        $agendaData = $meeting->meetingItemFormData($meetingItems);
        $meetingProjects = $agendaData['meetingProjects'];

        $meeting->setRelation('projects', $meetingProjects);

        $user = Auth::user();

        $availableProjects = Project::availableForMeetings($user)
            ->get()
            ->values();

        if ($availableProjects->where('id', $project->id)->isEmpty()) {
            $availableProjects->prepend($project);
        }
        $selectedProjects = old('projects', $meeting->projects->pluck('id')->all());

        return view('module-meetings.edit', array_merge(compact(
            'project',
            'meeting',
            'availableProjects',
            'selectedProjects',
            'meetingItems'
        ), $agendaData));
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

        $actor = Auth::user();
        $meeting->load('projects.users');
        $meeting->projects
            ->flatMap(fn(Project $project) => $project->users)
            ->unique('id')
            ->filter(fn($user) => !$actor || $user->id !== $actor->id)
            ->each(function ($user) use ($actor, $meeting) {
                Mail::to($user->email)->queue(new MeetingUpdated($user, $actor, $meeting));
            });

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Reuniao atualizada com sucesso!');
    }

    public function destroy(Project $project, Meeting $meeting)
    {
        Gate::authorize('delete', [$meeting, $project]);

        // Coleta os destinatarios da notificacao antes de deletar a reuniao,
        // para evitar problemas com o modelo deletado durante o processo de envio dos emails
        $actor = Auth::user();
        $meeting->load('projects.users');
        $recipients = $meeting->projects
            ->flatMap(fn(Project $project) => $project->users)
            ->unique('id')
            ->filter(fn($user) => !$actor || $user->id !== $actor->id);

        DB::transaction(function () use ($meeting) {
            $meeting->delete();
        });

        $recipients->each(function ($user) use ($actor, $meeting) {
            Mail::to($user->email)->queue(new MeetingUpdated($user, $actor, $meeting, true));
        });

        return redirect()->route('projects.meetings.index', $project)
            ->with('alert-success', 'Reuniao removida com sucesso!');
    }

    public function storeItem(StoreMeetingItemRequest $request, Project $project, Meeting $meeting)
    {
        $discussable = $request->discussable();

        $requestedOrder = (int) $request->validated('order');
        DB::transaction(function () use ($meeting, $discussable, $requestedOrder) {
            $maxOrder = (int) ($meeting->meetingItems()->max('order') ?? 0);
            $order = $requestedOrder;

            if ($order > $maxOrder + 1) {
                $order = $maxOrder + 1;
            }

            $meeting->meetingItems()
                ->where('order', '>=', $order)
                ->increment('order');

            MeetingItem::create([
                'meeting_id'       => $meeting->id,
                'discussable_type' => $discussable->getMorphClass(),
                'discussable_id'   => $discussable->getKey(),
                'order'            => $order,
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Item de pauta adicionado com sucesso!');
    }

    public function destroyItem(Project $project, Meeting $meeting, MeetingItem $meetingItem)
    {
        Gate::authorize('update', [$meeting, $project]);

        if ($meetingItem->meeting_id !== $meeting->id) {
            abort(404);
        }

        if ($meeting->status === MeetingStatus::COMPLETED) {
            return redirect()->back()
                ->withErrors(['meeting_item' => 'Nao é possivel remover itens de uma reunião já concluida.']);
        }

        DB::transaction(function () use ($meeting, $meetingItem) {
            $removedOrder = $meetingItem->order;

            $meetingItem->delete();

            $meeting->meetingItems()
                ->where('order', '>', $removedOrder)
                ->decrement('order');
        });

        return redirect()->back()
            ->with('alert-success', 'Item de pauta removido com sucesso!');
    }

    public function updateStatus(UpdateMeetingStatusRequest $request, Project $project, Meeting $meeting)
    {
        DB::transaction(function () use ($request, $meeting) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $meeting->update($data);
        });

        $actor = Auth::user();
        $meeting->load('projects.users');
        $meeting->projects
            ->flatMap(fn(Project $project) => $project->users)
            ->unique('id')
            ->filter(fn($user) => !$actor || $user->id !== $actor->id)
            ->each(function ($user) use ($actor, $meeting) {
                Mail::to($user->email)->queue(new MeetingUpdated($user, $actor, $meeting));
            });

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Status da reunião atualizado com sucesso!');
    }
}
