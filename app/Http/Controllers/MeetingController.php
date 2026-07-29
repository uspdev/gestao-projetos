<?php

namespace App\Http\Controllers;

use App\Enums\Meeting\MeetingStatus;
use App\Enums\Watch\WatchEventType;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingNotesRequest;
use App\Http\Requests\MeetingItem\StoreMeetingItemRequest;
use App\Http\Requests\MeetingItem\UpdateMeetingItemNotesRequest;
use App\Http\Requests\MeetingItem\UpdateMeetingItemTitleRequest;
use App\Http\Requests\Meeting\UpdateMeetingStatusRequest;
use App\Http\Requests\Meeting\UpdateMeetingAtaRequest;
use App\Http\Requests\Meeting\UpdateMeetingTranscriptionRequest;
use App\Models\Meeting;
use App\Models\PendingWatchNotification;
use App\Models\Project;
use App\Models\MeetingItem;
use App\Services\MentionIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        })->only(['index', 'create', 'show', 'edit', 'export']);
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

    public function store(StoreMeetingRequest $request, Project $project, MentionIndexer $mentionIndexer)
    {
        $meeting = DB::transaction(function () use ($request, $mentionIndexer) {
            $data = $request->validated();
            $projects = $data['projects'] ?? [];
            unset($data['projects']);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['status'] = MeetingStatus::DRAFT->value;

            $meeting = Meeting::create($data);
            $meeting->projects()->sync($projects);
            $mentionIndexer->validateAllMentions($meeting, 'notes', $data['notes'] ?? null);
            $mentionIndexer->synchronize($meeting, 'notes', $data['notes'] ?? null, Auth::id());

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
        $files = $meeting->media()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'files_page');
        $sharedFiles = $meeting->sharedFiles()
            ->with('uploader')
            ->latest()
            ->get();

        return view('module-meetings.show', array_merge(
            compact('project', 'meeting', 'meetingItems', 'files', 'sharedFiles'),
            $agendaData
        ));
    }
    /**
     * Exporta o conteúdo de uma reunião em formato TXT.
     *
     *
     * O arquivo gerado contém o título da reunião, os itens da reunião com suas
     * notas, as notas gerais e os comentários, conforme definido em
     * meetingExportContent().
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function export(Project $project, Meeting $meeting)
    {
        Gate::authorize('view', [$meeting, $project]);

        $meeting->load([
            'comments' => fn($query) => $query->active()->with('user')->oldest(),
            'meetingItems' => fn($query) => $query->with('discussable')->orderBy('order'),
        ]);

        $content = $this->meetingExportContent($meeting);
        $filename = sprintf(
            'reuniao-%s-%s.txt',
            Str::slug($meeting->title) ?: 'sem-titulo',
            now()->format('YmdHis')
        );

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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

        $this->notifyMeetingUpdated($meeting, $originalStatus);

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Reuniao atualizada com sucesso!');
    }

    public function updateMeetingNotes(UpdateMeetingNotesRequest $request, Project $project, Meeting $meeting, MentionIndexer $mentionIndexer)
    {
        Gate::authorize('update', [$meeting, $project]);

        DB::transaction(function () use ($request, $meeting, $mentionIndexer) {
            $notes = $request->validated('meeting_notes');
            $notes = is_string($notes) ? trim($notes) : $notes;

            $mentionIndexer->validateNewMentions($meeting, 'notes', $notes);
            $meeting->update([
                'notes' => $notes === '' ? null : $notes,
                'updated_by' => Auth::id(),
            ]);
            $mentionIndexer->synchronize($meeting, 'notes', $notes, Auth::id());
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
        $shouldNotify = $meeting->status !== MeetingStatus::DRAFT;

        DB::transaction(function () use ($meeting) {
            $meeting->delete();
        });

        if ($shouldNotify && ($actor = Auth::user())) {
            PendingWatchNotification::addForWatchers(
                $meeting,
                WatchEventType::MEETING_REMOVED,
                $actor,
                'Reunião removida.',
                null,
                null,
            );
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

    public function updateNotes(UpdateMeetingItemNotesRequest $request, Project $project, Meeting $meeting, MeetingItem $meetingItem, MentionIndexer $mentionIndexer)
    {
        if ($meetingItem->meeting_id !== $meeting->id) {
            abort(404);
        }

        DB::transaction(function () use ($request, $meetingItem, $mentionIndexer): void {
            $notes = $request->validated('notes');
            $notes = is_string($notes) ? trim($notes) : $notes;

            $mentionIndexer->validateNewMentions($meetingItem, 'notes', $notes);
            $meetingItem->notes = $notes === '' ? null : $notes;
            $meetingItem->save();
            $mentionIndexer->synchronize($meetingItem, 'notes', $notes, Auth::id());
        });

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

        $this->notifyMeetingUpdated($meeting, $originalStatus);

        return redirect()->route('projects.meetings.show', [$project, $meeting])
            ->with('alert-success', 'Status da reunião atualizado com sucesso!');
    }

    private function notifyMeetingUpdated(Meeting $meeting, MeetingStatus $originalStatus): void
    {
        $actor = Auth::user();

        if (! $actor || $meeting->status === MeetingStatus::DRAFT) {
            return;
        }

        $summary = $originalStatus === MeetingStatus::DRAFT
            ? 'Reunião agendada.'
            : 'Reunião atualizada.';

        PendingWatchNotification::addForWatchers(
            $meeting,
            WatchEventType::MEETING_UPDATED,
            $actor,
            $summary,
            null,
            $meeting->watchUrl(),
        );
    }

    /**
     * Monta o conteúdo textual da reunião para exportação em TXT.
     *
     * Inclui o título da reunião, os itens com suas respectivas notas,
     * as notas gerais e os comentários ativos da reunião.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return string
     */
    private function meetingExportContent(Meeting $meeting): string
    {
        $sections = [
            $this->plainText($meeting->title),
        ];

        foreach ($meeting->meetingItems as $item) {
            $sections[] = trim(implode(PHP_EOL, array_filter([
                $this->plainText($this->meetingItemTitle($item)),
                $this->plainText($item->notes),
            ], fn($line) => $line !== '')));
        }

        $sections[] = trim(implode(PHP_EOL, array_filter([
            'Notas gerais da reunião:',
            $this->plainText($meeting->notes),
        ], fn($line) => $line !== '')));

        $sections[] = trim(implode(PHP_EOL, array_filter([
            'Comentários da reunião:',
            $meeting->comments
                ->map(fn($comment) => $this->meetingCommentText($comment))
                ->filter()
                ->implode(PHP_EOL . PHP_EOL),
        ], fn($line) => $line !== '')));

        return collect($sections)
            ->implode(PHP_EOL . PHP_EOL) . PHP_EOL;
    }

    /**
     * Retorna o título exibível de um item da reunião.
     *
     * Usa o relacionamento discussable quando disponível, priorizando
     * title, depois name. Caso não exista um título válido, retorna
     * uma identificação baseada na ordem do item.
     *
     * @param  \App\Models\MeetingItem  $item
     * @return string
     */
    private function meetingItemTitle(MeetingItem $item): string
    {
        $discussable = $item->discussable;

        if (! $discussable) {
            return 'Item #' . $item->order . ':';
        }

        return $discussable->title
            ?? ($discussable->name . ':')
            ?? ('Item #' . $item->order . ':');
    }

    /**
     * Formata um comentário da reunião para texto simples.
     *
     * Retorna o comentário no formato "Autor: texto". Caso o comentário
     * não possua conteúdo textual válido, retorna uma string vazia.
     *
     * @param  mixed  $comment
     * @return string
     */
    private function meetingCommentText($comment): string
    {
        $author = $this->plainText($comment->user?->name ?? 'Usuário');
        $text = $this->plainText($comment->text);

        if ($text === '') {
            return '';
        }

        return $author . ': ' . $text;
    }

    /**
     * Converte um texto HTML em texto simples.
     *
     * Remove tags HTML, decodifica entidades, normaliza quebras de linha
     * e remove espaços ou linhas em excesso.
     *
     * @param  string|null  $text
     * @return string
     */
    private function plainText(?string $text): string
    {
        if (! filled($text)) {
            return '';
        }

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normaliza quebras de linha para Unix-style e remove espaços antes de quebras de linha
        $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
