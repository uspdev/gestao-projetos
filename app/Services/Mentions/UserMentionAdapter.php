<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class UserMentionAdapter
{
    public const ALIAS = 'user';

    public function supports(string $type): bool
    {
        return $type === self::ALIAS;
    }

    public function exists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && User::query()->whereKey($key)->exists();
    }

    public function isEligible(Model $source, string $key, ?User $reader = null): bool
    {
        return $this->exists($key)
            && $this->eligibleIds($source)->contains((int) $key);
    }

    /**
     * @return Collection<int, int>
     */
    public function eligibleIds(?Model $source): Collection
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
            $source instanceof Comment => $this->eligibleIds($source->commentable),
            default => collect(),
        })->map(fn (mixed $id): int => (int) $id)->unique()->values();
    }

    /**
     * @return Collection<int, array{id: int, name: string, type: string, type_label: string, group: string}>
     */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        return User::query()
            ->whereIn('id', $this->eligibleIds($source))
            ->when(trim($term) !== '', fn ($query) => $query->where('name', 'like', '%' . trim($term) . '%'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'type' => self::ALIAS,
                'type_label' => 'Pessoa',
                'group' => 'people',
            ])
            ->values();
    }

    /**
     * @return array{status: string, type: string, label?: string, url?: string, message?: string, accessible_name?: string}
     */
    public function present(string $key, ?User $reader): array
    {
        if (! $this->exists($key)) {
            return [
                'status' => 'missing',
                'type' => 'usuário',
                'message' => 'Menção a usuário: destino não encontrado',
            ];
        }

        $user = User::query()->find($key);

        if (! $reader || ! $reader->can('view', $user)) {
            return [
                'status' => 'forbidden',
                'type' => 'usuário',
                'message' => 'Menção a usuário: você não tem permissão para visualizar',
            ];
        }

        return [
            'status' => 'available',
            'type' => 'usuário',
            'label' => $user->name,
            'url' => deep_link('users.show', $user),
            'accessible_name' => 'usuário: ' . $user->name,
        ];
    }
}
