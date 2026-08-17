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
use App\Models\Watch;
use App\Services\Mentions\MentionManager;
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
        })->only(['index', 'create', 'show', 'export']);
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

    public function store(StoreMeetingRequest $request, Project $project, MentionManager $mentionManager)
    {
        $meeting = DB::transaction(function () use ($request, $mentionManager) {
            $data = $request->validated();
            $projects = $data['projects'] ?? [];
            unset($data['projects']);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['status'] = MeetingStatus::DRAFT->value;

            $meeting = Meeting::create($data);
            $meeting->projects()->sync($projects);
            Watch::enableForUsers(
                DB::table('project_user')
                    ->whereIn('project_id', $projects)
                    ->pluck('user_id'),
                $meeting,
            );
            $mentionManager->validateAllMentions($meeting, 'notes', $data['notes'] ?? null);
            $mentionManager->synchronize($meeting, 'notes', $data['notes'] ?? null);

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
        $availableProjects = collect();
        $selectedProjects = [];

        if (Gate::allows('update', [$meeting, $project])) {
            $availableProjects = Project::availableForMeetings(Auth::user())
                ->get()
                ->values();

            if ($availableProjects->where('id', $project->id)->isEmpty()) {
                $availableProjects->prepend($project);
            }

            $selectedProjects = old('projects', $meetingProjects->pluck('id')->all());
        }

        $files = $meeting->media()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'files_page');
        $links = \Illuminate\Support\Facades\Schema::hasTable('links')
            ? $meeting->links()
                ->with('creator')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'links_page')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        $sharedFiles = $meeting->sharedFiles()
            ->with('uploader')
            ->latest()
            ->get();
        $sharedLinks = \Illuminate\Support\Facades\Schema::hasTable('meeting_link_shares')
            ? $meeting->sharedLinks()
                ->with('creator')
                ->latest()
                ->get()
            : collect();

        return view('module-meetings.show', array_merge(
            compact(
                'project',
                'meeting',
                'meetingItems',
                'files',
                'links',
                'sharedFiles',
                'sharedLinks',
                'availableProjects',
                'selectedProjects'
            ),
            $agendaData
        ));
    }
    /**
     * Exporta o conteúdo de uma reunião em formato TXT.
     *
     * O arquivo gerado contém instruções de contexto para IA, link direto,
     * informações gerais, projetos vinculados, pauta, registros e comentários.
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
            'projects' => fn($query) => $query->orderBy('name'),
        ]);

        $content = $this->meetingExportContent($meeting, $project);
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

    public function updateMeetingNotes(UpdateMeetingNotesRequest $request, Project $project, Meeting $meeting, MentionManager $mentionManager)
    {
        Gate::authorize('update', [$meeting, $project]);

        DB::transaction(function () use ($request, $meeting, $mentionManager) {
            $notes = $request->validated('meeting_notes');
            $notes = is_string($notes) ? trim($notes) : $notes;

            $mentionManager->validateNewMentions($meeting, 'notes', $notes);
            $meeting->update([
                'notes' => $notes === '' ? null : $notes,
                'updated_by' => Auth::id(),
            ]);
            $mentionManager->synchronize($meeting, 'notes', $notes);
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

    public function updateNotes(UpdateMeetingItemNotesRequest $request, Project $project, Meeting $meeting, MeetingItem $meetingItem, MentionManager $mentionManager)
    {
        if ($meetingItem->meeting_id !== $meeting->id) {
            abort(404);
        }

        DB::transaction(function () use ($request, $meetingItem, $mentionManager): void {
            $notes = $request->validated('notes');
            $notes = is_string($notes) ? trim($notes) : $notes;

            $mentionManager->validateNewMentions($meetingItem, 'notes', $notes);
            $meetingItem->notes = $notes === '' ? null : $notes;
            $meetingItem->save();
            $mentionManager->synchronize($meetingItem, 'notes', $notes);
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

        PendingWatchNotification::dispatchMeetingUpdateForWatchers(
            $meeting,
            $actor,
            $summary,
        );
    }

    /**
     * Monta o conteúdo textual da reunião para exportação em TXT.
     *
     * Mantém todos os rótulos, mesmo quando o respectivo conteúdo estiver
     * vazio, para oferecer uma estrutura previsível a leitores e ferramentas.
     *
     * @param  \App\Models\Meeting  $meeting
     * @param  \App\Models\Project  $project
     * @return string
     */
    private function meetingExportContent(Meeting $meeting, Project $project): string
    {
        $instructions = implode(PHP_EOL, [
            'instruções para IA:',
            'Use somente as informações registradas abaixo e não invente dados ausentes.',
        ]);
        $meetingInformation = implode(PHP_EOL, [
            $this->exportInlineField('link direto', route('projects.meetings.show', [$project, $meeting])),
            $this->exportInlineField('nome da reunião', $meeting->title),
            $this->exportInlineField('data e hora', $meeting->scheduled_at?->format('d/m/Y H:i')),
            $this->exportInlineField('local', $meeting->location),
            $this->exportInlineField('projetos vinculados', $meeting->projects
                ->map(fn(Project $linkedProject) => $this->plainText($linkedProject->name))
                ->filter()
                ->implode(', ')),
        ]);
        $agenda = ['itens de pauta:'];

        foreach ($meeting->meetingItems as $item) {
            $agenda[] = $this->exportInlineField('item ' . $item->order, $this->meetingItemTitle($item));
            $agenda[] = $this->exportInlineField('conteúdo', $item->notes);
        }

        $comments = $meeting->comments
            ->map(fn($comment) => $this->meetingCommentText($comment))
            ->filter()
            ->implode(PHP_EOL . PHP_EOL);

        return implode(PHP_EOL . PHP_EOL, [
            $instructions,
            $meetingInformation,
            $this->exportSection('anotações prévias', $meeting->notes),
            implode(PHP_EOL, $agenda),
            $this->exportSection('ata', $meeting->ata),
            $this->exportSection('transcrição', $meeting->transcription),
            $this->exportSection('comentários', $comments),
        ]) . PHP_EOL;
    }

    private function exportInlineField(string $label, ?string $content): string
    {
        $content = $this->plainText($content);

        return $label . ':' . ($content === '' ? '' : ' ' . $content);
    }

    private function exportSection(string $label, ?string $content): string
    {
        $content = $this->plainText($content);

        return $label . ':' . ($content === '' ? '' : PHP_EOL . $content);
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

        if (filled($item->title)) {
            return $item->title;
        }

        if (! $discussable) {
            return 'Item #' . $item->order;
        }

        return $discussable->title
            ?? $discussable->name
            ?? ('Item #' . $item->order);
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
