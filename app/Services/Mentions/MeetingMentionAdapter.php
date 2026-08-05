<?php

namespace App\Services\Mentions;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class MeetingMentionAdapter
{
    public const ALIAS = 'meeting';

    private MentionProjectContextResolver $contextResolver;

    public function __construct(?MentionProjectContextResolver $contextResolver = null)
    {
        $this->contextResolver = $contextResolver ?? new MentionProjectContextResolver();
    }

    public function supports(string $type): bool
    {
        return $type === self::ALIAS;
    }

    public function exists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && Meeting::query()->whereKey($key)->exists();
    }

    public function historicalExists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && Meeting::withTrashed()->whereKey($key)->exists();
    }

    public function isEligible(Model $source, string $key, ?User $reader = null): bool
    {
        $meeting = $this->resolve($key);

        if (! $meeting || ($source instanceof Meeting && $source->is($meeting))) {
            return false;
        }

        return $reader !== null && $meeting->contextProjectFor($reader) !== null;
    }

    /**
     * @return Collection<int, array{id: int, name: string, type: string, type_label: string, group: string}>
     */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        if (! $reader || ! Schema::hasTable('meetings')) {
            return collect();
        }

        $meetings = Meeting::query()
            ->with('projects')
            ->when(trim($term) !== '', fn ($query) => $query->where(
                'title',
                'like',
                '%' . trim($term) . '%'
            ))
            ->orderBy('title')
            ->get(['id', 'title']);
        $contextMeetingIds = $this->contextMeetingIds($source);
        $excludedIds = $source instanceof Meeting
            ? collect([(int) $source->getKey()])
            : collect();

        $visible = $meetings
            ->reject(fn (Meeting $meeting): bool => $excludedIds->contains((int) $meeting->getKey()))
            ->filter(fn (Meeting $meeting): bool => $meeting->contextProjectFor($reader) !== null);
        $contextual = $visible
            ->filter(fn (Meeting $meeting): bool => $contextMeetingIds->contains((int) $meeting->getKey()))
            ->sortBy(fn (Meeting $meeting): int => (int) $contextMeetingIds->search((int) $meeting->getKey()))
            ->values();
        $global = $visible
            ->reject(fn (Meeting $meeting): bool => $contextMeetingIds->contains((int) $meeting->getKey()))
            ->values();

        return $contextual
            ->concat($global)
            ->map(fn (Meeting $meeting): array => $this->result($meeting))
            ->values();
    }

    /**
     * @return array{status: string, type: string, label?: string, url?: string, message?: string, accessible_name?: string}
     */
    public function present(string $key, ?User $reader): array
    {
        $meeting = Meeting::withTrashed()->whereKey($key)->first();

        if (! $meeting || $meeting->trashed()) {
            return [
                'status' => 'missing',
                'type' => 'reunião',
                'message' => 'Menção a reunião: destino não encontrado',
            ];
        }

        $project = $reader ? $meeting->contextProjectFor($reader) : null;

        if (! $project) {
            return [
                'status' => 'forbidden',
                'type' => 'reunião',
                'message' => 'Menção a reunião: você não tem permissão para visualizar',
            ];
        }

        return [
            'status' => 'available',
            'type' => 'reunião',
            'label' => $meeting->title,
            'url' => route('projects.meetings.show', [$project, $meeting]),
            'accessible_name' => 'reunião: ' . $meeting->title,
        ];
    }

    private function resolve(string $key): ?Meeting
    {
        if (! preg_match('/^[1-9][0-9]*$/', $key)) {
            return null;
        }

        return Meeting::query()->find($key);
    }

    /** @return Collection<int, int> */
    private function contextMeetingIds(Model $source): Collection
    {
        return $this->contextResolver->forMeetingSearch($source)
            ->flatMap(fn (Project $project) => $project->loadMissing('meetings')->meetings->pluck('id'))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /** @return array{id: int, name: string, type: string, type_label: string, group: string} */
    private function result(Meeting $meeting): array
    {
        return [
            'id' => (int) $meeting->getKey(),
            'name' => $meeting->title,
            'type' => self::ALIAS,
            'type_label' => 'Reunião',
            'group' => 'meetings',
        ];
    }
}
