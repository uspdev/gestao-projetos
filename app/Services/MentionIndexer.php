<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MentionIndexer
{
    public function __construct(private MentionExtractor $extractor)
    {
    }

    /**
     * @return Collection<int, int>
     */
    public function eligibleUserIds(?Model $source): Collection
    {
        if (! $source) {
            return collect();
        }

        return (match (true) {
            $source instanceof Project => $source->users()->pluck('users.id'),
            $source instanceof Task => $source->project->users()->pluck('users.id'),
            $source instanceof Meeting => $source->projects()
                ->with('users:id')
                ->get()
                ->flatMap(fn (Project $project) => $project->users->pluck('id')),
            $source instanceof MeetingItem => $source->meeting->projects()
                ->with('users:id')
                ->get()
                ->flatMap(fn (Project $project) => $project->users->pluck('id')),
            $source instanceof Comment => $this->eligibleUserIds($source->commentable),
            default => collect(),
        })->map(fn (mixed $id): int => (int) $id)->unique()->values();
    }

    public function validateNewMentions(Model $source, string $field, ?string $markdown): void
    {
        $currentMarkdown = $source->getRawOriginal($field);
        $newIds = collect($this->extractor->extract($markdown))
            ->diff($this->extractor->extract(is_string($currentMarkdown) ? $currentMarkdown : null));

        $this->assertEligible($source, $field, $newIds);
    }

    public function validateAllMentions(Model $source, string $field, ?string $markdown): void
    {
        $this->assertEligible($source, $field, collect($this->extractor->extract($markdown)));
    }

    public function clear(Model $source): void
    {
        $source->mentions()->delete();
    }

    /**
     * @param Collection<int, int> $mentionedUserIds
     */
    private function assertEligible(Model $source, string $field, Collection $mentionedUserIds): void
    {
        $ineligibleIds = $mentionedUserIds->diff($this->eligibleUserIds($source));

        if ($ineligibleIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => 'Uma ou mais Menções não são permitidas neste contexto.',
            ]);
        }
    }

    public function synchronize(Model $source, string $field, ?string $markdown, ?int $createdBy): void
    {
        $mentionedUserIds = collect($this->extractor->extract($markdown));
        $mentions = $source->mentions()->where('field', $field)->get()->keyBy('mentioned_user_id');

        $mentions
            ->reject(fn ($mention): bool => $mentionedUserIds->contains((int) $mention->mentioned_user_id))
            ->each->delete();

        $mentionedUserIds
            ->reject(fn (int $userId): bool => $mentions->has($userId))
            ->each(fn (int $userId) => $source->mentions()->create([
                'field' => $field,
                'mentioned_user_id' => $userId,
                'created_by' => $createdBy,
            ]));
    }

    public function rebuildSource(Model $source): int
    {
        $fields = $this->markdownFields($source);
        $createdBy = $this->sourceAuthorId($source);

        foreach ($fields as $field) {
            $this->synchronize($source, $field, $source->{$field}, $createdBy);
        }

        return count($fields);
    }

    /**
     * @return list<string>
     */
    public function markdownFields(Model $source): array
    {
        return match (true) {
            $source instanceof Project, $source instanceof Task => ['description'],
            $source instanceof Meeting, $source instanceof MeetingItem => ['notes'],
            $source instanceof Comment => $source->is_active ? ['text'] : [],
            default => [],
        };
    }

    private function sourceAuthorId(Model $source): ?int
    {
        return match (true) {
            $source instanceof Comment => $source->user_id,
            $source instanceof MeetingItem => $source->meeting?->created_by,
            default => $source->created_by,
        };
    }
}
