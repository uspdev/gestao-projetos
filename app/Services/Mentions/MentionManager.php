<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Mention;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Morphs\MentionMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class MentionManager
{
    public function __construct(
        private MentionExtractor $extractor,
        ?UserMentionAdapter $userAdapter = null,
        ?ProjectMentionAdapter $projectAdapter = null,
        ?TaskMentionAdapter $taskAdapter = null,
        ?MeetingMentionAdapter $meetingAdapter = null,
        ?FileMentionAdapter $fileAdapter = null,
        ?MentionProjectContextResolver $contextResolver = null,
        ?MentionContextualSearch $contextualSearch = null,
    ) {
        $this->userAdapter = $userAdapter ?? new UserMentionAdapter();
        $contextResolver ??= new MentionProjectContextResolver();
        $contextualSearch ??= new MentionContextualSearch();
        $this->projectAdapter = $projectAdapter ?? new ProjectMentionAdapter($contextResolver, $contextualSearch);
        $this->taskAdapter = $taskAdapter ?? new TaskMentionAdapter($contextResolver, $contextualSearch);
        $this->meetingAdapter = $meetingAdapter ?? new MeetingMentionAdapter($contextResolver);
        $this->fileAdapter = $fileAdapter ?? new FileMentionAdapter();
    }

    private UserMentionAdapter $userAdapter;
    private ProjectMentionAdapter $projectAdapter;
    private TaskMentionAdapter $taskAdapter;
    private MeetingMentionAdapter $meetingAdapter;
    private FileMentionAdapter $fileAdapter;

    public function synchronize(
        Model $source,
        string $field,
        ?string $markdown,
        bool $strict = true,
        ?User $actor = null,
    ): void
    {
        DB::transaction(function () use ($source, $field, $markdown, $strict, $actor): void {
            if ($this->sourceIsUnavailable($source)) {
                $this->clear($source);

                return;
            }

            $references = $strict
                ? collect($this->referencesOrFail($field, $markdown))
                : collect($this->extractor->references($markdown, false));

            if ($strict) {
                $currentMarkdown = $source->getRawOriginal($field);
                $currentReferences = $this->referencesOrFail(
                    $field,
                    is_string($currentMarkdown) ? $currentMarkdown : null
                );
                $currentIdentities = collect($currentReferences)
                    ->map(fn (MentionReference $reference): string => $reference->identity());
                $newReferences = $references->reject(
                    fn (MentionReference $reference): bool => $currentIdentities->contains($reference->identity())
                );

                $this->assertEligible($source, $field, $newReferences, $actor);
            }

            $references = $references
                ->filter(fn (MentionReference $reference): bool => $this->targetExists($reference, true))
                ->mapWithKeys(function (MentionReference $reference): array {
                    $identity = $this->relationIdentity($reference);

                    return $identity === null ? [] : [$identity => $reference];
                });
            $mentions = $source->outgoingMentions()
                ->where('source_field', $field)
                ->get()
                ->keyBy(fn (Mention $mention): string => $mention->target_type . ':' . $mention->target_id);

            $mentions
                ->reject(fn (Mention $mention): bool => $references->has($mention->target_type . ':' . $mention->target_id))
                ->each->delete();

            $references
                ->reject(fn (MentionReference $reference): bool => $mentions->has($this->relationIdentity($reference)))
                ->each(fn (MentionReference $reference) => $source->outgoingMentions()->create([
                    'source_field' => $field,
                    'target_type' => $reference->type,
                    'target_id' => $this->relationTargetId($reference),
                ]));
        });
    }

    public function validateNewMentions(
        Model $source,
        string $field,
        ?string $markdown,
        ?User $actor = null,
    ): void
    {
        $references = $this->referencesOrFail($field, $markdown);
        $currentMarkdown = $source->getRawOriginal($field);
        $currentReferences = $this->referencesOrFail(
            $field,
            is_string($currentMarkdown) ? $currentMarkdown : null
        );
        $currentIdentities = collect($currentReferences)
            ->map(fn (MentionReference $reference): string => $reference->identity());

        $this->assertEligible(
            $source,
            $field,
            collect($references)->reject(
                fn (MentionReference $reference): bool => $currentIdentities->contains($reference->identity())
            ),
            $actor,
        );
    }

    public function validateAllMentions(
        Model $source,
        string $field,
        ?string $markdown,
        ?User $actor = null,
    ): void
    {
        $this->assertEligible(
            $source,
            $field,
            collect($this->referencesOrFail($field, $markdown)),
            $actor,
        );
    }

    public function clear(Model $source): void
    {
        $source->outgoingMentions()->delete();
    }

    public function rebuildSource(Model $source): int
    {
        if (! Schema::hasTable('mentions')) {
            return 0;
        }

        if ($this->sourceIsUnavailable($source)) {
            $this->clear($source);

            return 0;
        }

        $fields = $this->markdownFields($source);

        if ($fields === []) {
            $source->outgoingMentions()->delete();

            return 0;
        }

        $source->outgoingMentions()
            ->whereNotIn('source_field', $fields)
            ->delete();

        foreach ($fields as $field) {
            $this->synchronize($source, $field, $source->{$field}, false);
        }

        return count($fields);
    }

    /**
     * @return array{sources: int, mentions: int, errors: list<array{source: string, message: string}>}
     */
    public function rebuild(): array
    {
        $counts = [
            'sources' => 0,
            'mentions' => 0,
            'errors' => [],
        ];

        foreach (MentionMap::sourceOptions() as $modelClass) {
            if (! is_string($modelClass) || ! class_exists($modelClass)
                || ! Schema::hasTable((new $modelClass())->getTable())) {
                continue;
            }

            $query = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
                ? $modelClass::withTrashed()
                : $modelClass::query();

            $query->chunkById(100, function (Collection $sources) use (&$counts): void {
                $sources->each(function (Model $source) use (&$counts): void {
                    try {
                        if ($this->sourceIsUnavailable($source)) {
                            $this->clear($source);
                        } else {
                            $this->rebuildSource($source);
                        }

                        $counts['sources']++;
                        $counts['mentions'] += $source->outgoingMentions()->count();
                    } catch (Throwable $exception) {
                        $counts['errors'][] = [
                            'source' => class_basename($source) . ' #' . $source->getKey(),
                            'message' => $exception->getMessage(),
                        ];
                    }
                });
            });
        }

        return $counts;
    }

    /**
     * @return list<string>
     */
    public function markdownFields(Model $source): array
    {
        return match (true) {
            $source instanceof \App\Models\Project,
            $source instanceof \App\Models\Task => ['description'],
            $source instanceof \App\Models\Meeting,
            $source instanceof \App\Models\MeetingItem => ['notes'],
            $source instanceof \App\Models\Comment => $source->is_active ? ['text'] : [],
            default => [],
        };
    }

    /**
     * @return Collection<int, int>
     */
    public function eligibleUserIds(?Model $source): Collection
    {
        return $this->userAdapter->eligibleIds($source);
    }

    /**
     * @return Collection<int, array{id: int|string, name: string, type: string, type_label: string, group: string, scope?: 'contextual'|'global', completed?: bool}>
     */
    public function search(
        Model $source,
        string $term = '',
        ?User $reader = null,
        ?string $filter = null,
    ): Collection
    {
        $reader ??= Auth::user();
        $adapters = $this->adapters();
        $aliases = $this->searchAliases($filter);

        return collect($adapters)
            ->filter(fn (object $adapter, string $alias): bool => in_array($alias, $aliases, true))
            ->flatMap(fn (object $adapter): Collection => $adapter->search($source, $term, $reader))
            ->values();
    }

    /**
     * Retorna somente as relações de saída cujo texto e destino podem ser
     * visualizados pelo leitor informado.
     *
     * A relação Eloquent exposta pela fonte continua sendo o índice bruto
     * usado pela sincronização. Consultas para consumo devem passar por este
     * método para que uma Menção não se torne um caminho alternativo de
     * descoberta de destinos inacessíveis.
     */
    public function outgoingMentions(
        Model $source,
        ?User $reader = null,
        ?string $field = null,
    ): Collection
    {
        $reader ??= Auth::user();

        if (! $reader || ! $this->sourceIsVisible($source, $reader)
            || ! method_exists($source, 'outgoingMentions')) {
            return collect();
        }

        return $source->outgoingMentions()
            ->when($field !== null, fn ($query) => $query->where('source_field', $field))
            ->with('target')
            ->get()
            ->filter(fn (Mention $mention): bool => $this->targetIsVisible($mention, $reader))
            ->values();
    }

    /**
     * Retorna somente as relações de entrada cujo destino e fonte podem ser
     * visualizados pelo leitor informado.
     */
    public function incomingMentions(Model $target, ?User $reader = null): Collection
    {
        $reader ??= Auth::user();
        $alias = MentionMap::aliasForTarget($target);
        $adapter = $alias ? $this->adapterFor($alias) : null;

        if (! $reader || ! $alias || ! $adapter
            || $adapter->present($this->publicTargetKey($adapter, $target), $reader)['status'] !== 'available'
            || ! method_exists($target, 'incomingMentions')) {
            return collect();
        }

        return $target->incomingMentions()
            ->where('target_type', $alias)
            ->get()
            ->filter(fn (Mention $mention): bool => $this->sourceIsVisible($mention->source, $reader))
            ->values();
    }

    /**
     * @return array{status: string, type: string, label?: string, url?: string, message?: string, accessible_name?: string}
     */
    public function present(string $type, string $key, ?User $reader): array
    {
        $adapter = $this->adapterFor($type);

        if (! $adapter) {
            return [
                'status' => 'missing',
                'type' => $type,
                'message' => 'Menção a ' . $type . ': destino não encontrado',
            ];
        }

        return $adapter->present($key, $reader);
    }

    /**
     * @param Collection<int, MentionReference> $references
     */
    private function assertEligible(
        Model $source,
        string $field,
        Collection $references,
        ?User $actor = null,
    ): void
    {
        $ineligible = $references->contains(
            function (MentionReference $reference) use ($source, $actor): bool {
                $adapter = $this->adapterFor($reference->type);

                return ! $adapter || ! $adapter->isEligible($source, $reference->key, $actor ?? Auth::user());
            }
        );

        if ($ineligible) {
            throw ValidationException::withMessages([
                $field => 'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
            ]);
        }
    }

    /**
     * @return list<MentionReference>
     */
    private function referencesOrFail(string $field, ?string $markdown): array
    {
        try {
            return $this->extractor->references($markdown);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                $field => 'Uma ou mais Menções estão malformadas.',
            ]);
        }
    }

    private function targetExists(MentionReference $reference, bool $includeSoftDeleted = false): bool
    {
        $adapter = $this->adapterFor($reference->type);

        if (! $adapter) {
            return false;
        }

        if ($includeSoftDeleted && method_exists($adapter, 'historicalExists')) {
            return $adapter->historicalExists($reference->key);
        }

        return $adapter->exists($reference->key);
    }

    private function adapterFor(string $type): ?object
    {
        return collect($this->adapters())->first(
            fn (object $adapter): bool => $adapter->supports($type)
        );
    }

    /** @return array<string, object> */
    private function adapters(): array
    {
        return [
            UserMentionAdapter::ALIAS => $this->userAdapter,
            ProjectMentionAdapter::ALIAS => $this->projectAdapter,
            TaskMentionAdapter::ALIAS => $this->taskAdapter,
            MeetingMentionAdapter::ALIAS => $this->meetingAdapter,
            FileMentionAdapter::ALIAS => $this->fileAdapter,
        ];
    }

    /** @return list<string> */
    private function searchAliases(?string $filter): array
    {
        $filter = strtolower(trim((string) $filter));

        return match ($filter) {
            '', 'all', 'todos' => array_keys($this->adapters()),
            'user', 'users', 'people', 'pessoas' => [UserMentionAdapter::ALIAS],
            'project', 'projects', 'projeto', 'projetos' => [ProjectMentionAdapter::ALIAS],
            'task', 'tasks', 'tarefa', 'tarefas' => [TaskMentionAdapter::ALIAS],
            'meeting', 'meetings', 'reuniao', 'reunioes', 'reunião', 'reuniões' => [MeetingMentionAdapter::ALIAS],
            'file', 'files', 'arquivo', 'arquivos' => [FileMentionAdapter::ALIAS],
            default => [],
        };
    }

    private function relationTargetId(MentionReference $reference): ?string
    {
        $adapter = $this->adapterFor($reference->type);

        if (! $adapter) {
            return null;
        }

        return method_exists($adapter, 'relationKey')
            ? $adapter->relationKey($reference->key)
            : $reference->key;
    }

    private function relationIdentity(MentionReference $reference): ?string
    {
        $targetId = $this->relationTargetId($reference);

        return $targetId === null ? null : $reference->type . ':' . $targetId;
    }

    private function publicTargetKey(object $adapter, Model $target): string
    {
        return method_exists($adapter, 'publicKey')
            ? $adapter->publicKey($target)
            : (string) $target->getKey();
    }

    private function targetIsVisible(Mention $mention, User $reader): bool
    {
        $target = $mention->target;
        $adapter = $target instanceof Model
            ? $this->adapterFor($mention->target_type)
            : null;

        return $target instanceof Model
            && $adapter !== null
            && $adapter->present($this->publicTargetKey($adapter, $target), $reader)['status'] === 'available';
    }

    private function sourceIsUnavailable(Model $source): bool
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($source), true) && $source->trashed()) {
            return true;
        }

        if ($source instanceof Comment && ! $source->is_active) {
            return true;
        }

        if ($source instanceof MeetingItem) {
            $meeting = Meeting::withTrashed()->find($source->meeting_id);

            return $meeting === null || $meeting->trashed();
        }

        return false;
    }

    private function sourceIsVisible(?Model $source, User $reader): bool
    {
        if (! $source || $this->sourceIsUnavailable($source)) {
            return false;
        }

        return match (true) {
            $source instanceof Project,
            $source instanceof Task => Gate::forUser($reader)->allows('view', $source),
            $source instanceof Comment => $this->sourceIsVisible(
                $source->loadMissing('commentable')->commentable,
                $reader,
            ),
            $source instanceof Meeting => $source->loadMissing('projects')->projects
                ->contains(fn (Project $project): bool => Gate::forUser($reader)->allows('view', [$source, $project])),
            $source instanceof MeetingItem => $source->loadMissing('meeting.projects')->meeting?->projects
                ?->contains(fn (Project $project): bool => Gate::forUser($reader)->allows('view', [$source->meeting, $project])) ?? false,
            default => false,
        };
    }
}
