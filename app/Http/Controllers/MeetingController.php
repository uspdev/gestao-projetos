<?php

namespace App\Http\Controllers;

use App\Enums\Meeting\MeetingStatus;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingNotesRequest;
use App\Http\Requests\MeetingItem\StoreMeetingItemRequest;
use App\Http\Requests\MeetingItem\UpdateMeetingItemNotesRequest;
use App\Http\Requests\MeetingItem\UpdateMeetingItemTitleRequest;
use App\Http\Requests\Meeting\UpdateMeetingStatusRequest;
use App\Http\Requests\Meeting\UpdateMeetingAtaRequest;
use App\Http\Requests\Meeting\UpdateMeetingTranscriptionRequest;
use App\Mail\MeetingUpdated;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\MeetingItem;
use Illuminate\Http\Request;
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

    public function index(Request $request, Project $project)
    {
        Gate::authorize('viewAny', [Meeting::class, $project]);

        $showCompleted = $request->boolean('show_completed');

        $meetings = $project->meetings()
            ->with('projects')
            ->when(! $showCompleted, function ($query) {
                $query->where('status', '!=', MeetingStatus::COMPLETED->value);
            })
            ->orderBy('status', 'desc')
            ->orderBy('scheduled_at')
            ->get();

        return view('module-meetings.index', compact('project', 'meetings', 'showCompleted'));
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
            $data['status'] = MeetingStatus::DRAFT->value;

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
        $originalStatus = $meeting->status;

        DB::transaction(function () use ($request, $meeting) {
            $data = $request->validated();
            $projects = $data['projects'] ?? [];
            unset($data['projects']);

            $data['updated_by'] = Auth::id();

            $meeting->update($data);
            $meeting->projects()->sync($projects);
        });

        $this->notifyMeetingUsers($meeting, $originalStatus);

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Reuniao atualizada com sucesso!');
    }

    public function updateMeetingNotes(UpdateMeetingNotesRequest $request, Project $project, Meeting $meeting)
    {
        Gate::authorize('update', [$meeting, $project]);

        DB::transaction(function () use ($request, $meeting) {
            $notes = $request->validated('meeting_notes');
            $notes = is_string($notes) ? trim($notes) : $notes;

            $meeting->update([
                'notes' => $notes === '' ? null : $notes,
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Notas da reuniao atualizadas com sucesso!');
    }

    public function updateAta(UpdateMeetingAtaRequest $request, Project $project, Meeting $meeting)
    {
        Gate::authorize('update', [$meeting, $project]);

        $ata = trim((string) $request->validated('ata', ''));

        $meeting->update([
            'ata' => $ata === '' ? null : $ata,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()
            ->with('alert-success', 'Ata da reuniao atualizada com sucesso!');
    }

    public function updateTranscription(UpdateMeetingTranscriptionRequest $request, Project $project, Meeting $meeting)
    {
        Gate::authorize('update', [$meeting, $project]);

        $transcription = trim((string) $request->validated('transcription', ''));

        $meeting->update([
            'transcription' => $transcription === '' ? null : $transcription,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()
            ->with('alert-success', 'Transcricao da reuniao atualizada com sucesso!');
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

        if ($meeting->status !== MeetingStatus::DRAFT) {
            $recipients->each(function ($user) use ($actor, $meeting) {
                Mail::to($user->email)->queue(new MeetingUpdated($user, $actor, $meeting, true));
            });
        }

        return redirect()->route('projects.meetings.index', $project)
            ->with('alert-success', 'Reuniao removida com sucesso!');
    }

    public function storeItem(StoreMeetingItemRequest $request, Project $project, Meeting $meeting)
    {
        $discussable = $request->discussable();

        $requestedOrder = (int) $request->validated('order');
        DB::transaction(function () use ($request, $meeting, $discussable, $requestedOrder) {
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
                'discussable_type' => $request->isIndependent() ? null : $discussable->getMorphClass(),
                'discussable_id'   => $request->isIndependent() ? null : $discussable->getKey(),
                'title'            => $request->isIndependent() ? $request->validated('title') : null,
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

    public function updateNotes(UpdateMeetingItemNotesRequest $request, Project $project, Meeting $meeting, MeetingItem $meetingItem)
    {
        if ($meetingItem->meeting_id !== $meeting->id) {
            abort(404);
        }

        $notes = $request->validated('notes');
        $notes = is_string($notes) ? trim($notes) : $notes;

        $meetingItem->notes = $notes === '' ? null : $notes;
        $meetingItem->save();

        return redirect()->back()
            ->with('alert-success', 'Notas do item atualizadas com sucesso!');
    }

    public function updateItemTitle(UpdateMeetingItemTitleRequest $request, Project $project, Meeting $meeting, MeetingItem $meetingItem)
    {
        if ($meetingItem->meeting_id !== $meeting->id) {
            abort(404);
        }

        $meetingItem->update([
            'title' => $request->validated('title'),
        ]);

        return redirect()->back()
            ->with('alert-success', 'Título do item de pauta atualizado com sucesso!');
    }

    public function updateStatus(UpdateMeetingStatusRequest $request, Project $project, Meeting $meeting)
    {
        $originalStatus = $meeting->status;

        DB::transaction(function () use ($request, $meeting) {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $meeting->update($data);
        });

        $this->notifyMeetingUsers($meeting, $originalStatus);

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Status da reunião atualizado com sucesso!');
    }

    private function notifyMeetingUsers(Meeting $meeting, ?MeetingStatus $originalStatus = null, bool $isCancelled = false): void
    {
        if (! $this->shouldNotifyMeetingUsers($meeting, $originalStatus)) {
            return;
        }

        $actor = Auth::user();
        $meeting->load('projects.users');

        $meeting->projects
            ->flatMap(fn(Project $project) => $project->users)
            ->unique('id')
            ->filter(fn($user) => !$actor || $user->id !== $actor->id)
            ->each(function ($user) use ($actor, $meeting, $isCancelled) {
                Mail::to($user->email)->queue(new MeetingUpdated($user, $actor, $meeting, $isCancelled));
            });
    }

    private function shouldNotifyMeetingUsers(Meeting $meeting, ?MeetingStatus $originalStatus = null): bool
    {
        if ($meeting->status === MeetingStatus::DRAFT) {
            return false;
        }

        if ($originalStatus === MeetingStatus::DRAFT) {
            return $meeting->status === MeetingStatus::SCHEDULED;
        }

        return true;
    }
}
