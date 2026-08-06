<?php

namespace App\Services\Mentions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class MentionContextualSearch
{
    /**
     * @param Collection<int, Model> $targets
     * @param Collection<int, int> $contextIds
     * @param callable(Model): int $contextId
     * @return Collection<int, array{target: Model, scope: 'contextual'|'global'}>
     */
    public function prioritize(
        Collection $targets,
        string $term,
        Collection $contextIds,
        callable $contextId,
    ): Collection {
        $contextual = $targets
            ->filter(fn (Model $target): bool => $contextIds->contains((int) $contextId($target)))
            ->sortBy(fn (Model $target): int => (int) $contextIds->search((int) $contextId($target)))
            ->values();
        $global = $targets
            ->reject(fn (Model $target): bool => $contextIds->contains((int) $contextId($target)))
            ->values();
        $results = trim($term) === ''
            ? $contextual
            : $contextual->concat($global);

        return $results
            ->map(fn (Model $target): array => [
                'target' => $target,
                'scope' => $contextIds->contains((int) $contextId($target))
                    ? 'contextual'
                    : 'global',
            ])
            ->values();
    }
}
