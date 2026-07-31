<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Mention;
use App\Models\User;
use App\Morphs\MentionMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class MentionManager
{
    public function __construct(
        private MentionExtractor $extractor,
        ?UserMentionAdapter $userAdapter = null,
    ) {
        $this->userAdapter = $userAdapter ?? new UserMentionAdapter();
    }

    private UserMentionAdapter $userAdapter;

    public function synchronize(Model $source, string $field, ?string $markdown, bool $strict = true): void
    {
        DB::transaction(function () use ($source, $field, $markdown, $strict): void {
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

                $this->assertEligible($source, $field, $newReferences);
            }

            $references = $references
                ->filter(fn (MentionReference $reference): bool => $this->targetExists($reference))
                ->keyBy(fn (MentionReference $reference): string => $reference->identity());
            $mentions = $source->outgoingMentions()
                ->where('source_field', $field)
                ->get()
                ->keyBy(fn (Mention $mention): string => $mention->target_type . ':' . $mention->target_id);

            $mentions
                ->reject(fn (Mention $mention): bool => $references->has($mention->target_type . ':' . $mention->target_id))
                ->each->delete();

            $references
                ->reject(fn (MentionReference $reference): bool => $mentions->has($reference->identity()))
                ->each(fn (MentionReference $reference) => $source->outgoingMentions()->create([
                    'source_field' => $field,
                    'target_type' => $reference->type,
                    'target_id' => $reference->key,
                ]));
        });
    }

    public function validateNewMentions(Model $source, string $field, ?string $markdown): void
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
            )
        );
    }

    public function validateAllMentions(Model $source, string $field, ?string $markdown): void
    {
        $this->assertEligible($source, $field, collect($this->referencesOrFail($field, $markdown)));
    }

    public function clear(Model $source): void
    {
        $source->outgoingMentions()->delete();
    }

    public function rebuildSource(Model $source): int
    {
        if ($this->sourceIsUnavailable($source)) {
            $this->clear($source);

            return 0;
        }

        $fields = $this->markdownFields($source);

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
     * @return Collection<int, array{id: int, name: string}>
     */
    public function search(Model $source, string $term = ''): Collection
    {
        return $this->userAdapter->search($source, $term);
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
    private function assertEligible(Model $source, string $field, Collection $references): void
    {
        $ineligible = $references->contains(
            function (MentionReference $reference) use ($source): bool {
                $adapter = $this->adapterFor($reference->type);

                return ! $adapter || ! $adapter->isEligible($source, $reference->key);
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

    private function targetExists(MentionReference $reference): bool
    {
        $adapter = $this->adapterFor($reference->type);

        return $adapter?->exists($reference->key) ?? false;
    }

    private function adapterFor(string $type): ?UserMentionAdapter
    {
        return $this->userAdapter->supports($type) ? $this->userAdapter : null;
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
}
