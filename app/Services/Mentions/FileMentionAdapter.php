<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class FileMentionAdapter
{
    public const ALIAS = 'file';

    public function supports(string $type): bool
    {
        return $type === self::ALIAS;
    }

    public function exists(string $key): bool
    {
        $media = $this->resolve($key);

        return $media !== null && $this->activeOwner($media) !== null;
    }

    public function historicalExists(string $key): bool
    {
        return $this->resolve($key) !== null;
    }

    public function relationKey(string $key): ?string
    {
        $media = $this->resolve($key);

        return $media ? (string) $media->getKey() : null;
    }

    public function publicKey(Model $target): string
    {
        return (string) $target->getAttribute('uuid');
    }

    public function isEligible(Model $source, string $key, ?User $reader = null): bool
    {
        $media = $this->resolve($key);

        return $media !== null
            && $reader !== null
            && $this->contextFiles($source)->contains('uuid', $media->uuid)
            && Gate::forUser($reader)->allows('view', $media);
    }

    /** @return Collection<int, array{id: string, name: string, type: string, type_label: string, group: string}> */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        if (! $reader || ! Schema::hasTable('media')) {
            return collect();
        }

        $term = trim($term);

        return $this->contextFiles($source)
            ->unique('uuid')
            ->filter(fn (Media $media): bool => Gate::forUser($reader)->allows('view', $media))
            ->filter(fn (Media $media): bool => $term === '' || str_contains(
                mb_strtolower((string) $media->display_name),
                mb_strtolower($term),
            ))
            ->map(fn (Media $media): array => [
                'id' => (string) $media->uuid,
                'name' => (string) $media->display_name,
                'type' => self::ALIAS,
                'type_label' => 'Arquivo',
                'group' => 'files',
            ])
            ->values();
    }

    /** @return array{status: string, type: string, label?: string, url?: string, message?: string, accessible_name?: string} */
    public function present(string $key, ?User $reader): array
    {
        $media = $this->resolve($key);

        if (! $media || ! $this->activeOwner($media)) {
            return [
                'status' => 'missing',
                'type' => 'arquivo',
                'message' => 'Menção a arquivo: destino não encontrado',
            ];
        }

        if (! $reader || ! Gate::forUser($reader)->allows('view', $media)) {
            return [
                'status' => 'forbidden',
                'type' => 'arquivo',
                'message' => 'Menção a arquivo: você não tem permissão para visualizar',
            ];
        }

        return [
            'status' => 'available',
            'type' => 'arquivo',
            'label' => (string) $media->display_name,
            'url' => route('files.show', ['uuid' => $media->uuid]),
            'accessible_name' => 'arquivo: ' . $media->display_name,
        ];
    }

    private function resolve(string $key): ?Media
    {
        if (! Str::isUuid($key) || ! Schema::hasTable('media')) {
            return null;
        }

        return Media::query()->where('uuid', $key)->first();
    }

    private function activeOwner(Media $media): ?Model
    {
        $owner = $media->model;

        if (! $owner instanceof Model) {
            return null;
        }

        return ! method_exists($owner, 'trashed') || ! $owner->trashed()
            ? $owner
            : null;
    }

    /** @return Collection<int, Media> */
    private function contextFiles(Model $source): Collection
    {
        return match (true) {
            $source instanceof Project => $this->ownedFiles($source),
            $source instanceof Task => $this->ownedFiles($source)
                ->concat($source->loadMissing('project')->project
                    ? $this->ownedFiles($source->project)
                    : collect()),
            $source instanceof Meeting => $this->ownedFiles($source)
                ->concat($source->sharedFiles()->latest()->get()),
            $source instanceof MeetingItem => $source->loadMissing('meeting')->meeting
                ? $this->contextFiles($source->meeting)
                : collect(),
            $source instanceof Comment => $source->loadMissing('commentable')->commentable
                ? $this->contextFiles($source->commentable)
                : collect(),
            default => collect(),
        };
    }

    /** @return Collection<int, Media> */
    private function ownedFiles(Model $owner): Collection
    {
        return method_exists($owner, 'media')
            ? $owner->media()->latest()->get()
            : collect();
    }
}
