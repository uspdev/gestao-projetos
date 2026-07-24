<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Services\MentionIndexer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Illuminate\Console\Command;

class RebuildMentions extends Command
{
    protected $signature = 'mentions:rebuild';

    protected $description = 'Reconstrói o índice derivado de Menções a partir dos campos Markdown';

    public function handle(MentionIndexer $mentionIndexer): int
    {
        if (! Schema::hasTable('mentions')) {
            $this->error('A tabela mentions não existe. Execute as migrações antes da reconstrução.');

            return self::FAILURE;
        }

        $counts = ['sources' => 0, 'mentions' => 0, 'errors' => 0];

        foreach ([Project::class, Task::class, Meeting::class, MeetingItem::class, Comment::class] as $modelClass) {
            if (! Schema::hasTable((new $modelClass())->getTable())) {
                continue;
            }

            $query = match ($modelClass) {
                Project::class, Task::class, Meeting::class => $modelClass::withTrashed(),
                default => $modelClass::query(),
            };

            $query->chunkById(100, function ($sources) use ($mentionIndexer, &$counts): void {
                $sources->each(function (Model $source) use ($mentionIndexer, &$counts): void {
                    try {
                        if ((($source instanceof Project || $source instanceof Task || $source instanceof Meeting) && $source->trashed())
                            || ($source instanceof Comment && ! $source->is_active)
                            || ($source instanceof MeetingItem && $source->meeting?->trashed())) {
                            $mentionIndexer->clear($source);
                        } else {
                            $mentionIndexer->rebuildSource($source);
                        }

                        $counts['sources']++;
                        $counts['mentions'] += $source->mentions()->count();
                    } catch (Throwable $exception) {
                        $counts['errors']++;
                        $this->warn(sprintf('%s #%s: %s', class_basename($source), $source->getKey(), $exception->getMessage()));
                    }
                });
            });
        }

        $this->info(sprintf(
            'Reconstrução concluída: %d fontes, %d relações e %d erros.',
            $counts['sources'],
            $counts['mentions'],
            $counts['errors']
        ));

        return $counts['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
