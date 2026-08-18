<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Link;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectActivityService
{
    private const ACTION_LABELS = [
        'created' => 'criado',
        'updated' => 'alterado',
        'deleted' => 'excluído',
        'uploaded' => 'enviado',
        'renamed' => 'renomeado',
        'attached' => 'adicionado',
        'tag_attached' => 'adicionado',
        'detached' => 'removido',
        'tag_detached' => 'removido',
    ];

    private const EVENT_SEARCH_LABELS = [
        'criado' => 'created',
        'alterado' => 'updated',
        'excluído' => 'deleted',
        'enviado' => 'uploaded',
        'renomeado' => 'renamed',
        'adicionado' => 'attached',
        'removido' => 'detached',
    ];

    private const LOG_NAME_SEARCH_LABELS = [
        'projeto' => 'project',
        'tarefa' => 'task',
        'reunião' => 'meeting',
        'comentário' => 'comment',
        'arquivo' => 'file',
        'link' => 'link',
        'etiqueta' => 'tag',
    ];

    private const LOG_NAME_LABELS = [
        'project' => 'Projeto',
        'task' => 'Tarefa',
        'meeting' => 'Reunião',
        'meeting_item' => 'Item de pauta',
        'comment' => 'Comentário',
        'file' => 'Arquivo',
        'link' => 'Link',
        'tag' => 'Etiqueta',
    ];

    private const SUBJECT_SEARCH_FIELDS = [
        Project::class => [
            'label' => 'Projeto',
            'fields' => ['name', 'slug'],
        ],
        Task::class => [
            'label' => 'Tarefa',
            'fields' => ['title'],
        ],
        Meeting::class => [
            'label' => 'Reunião',
            'fields' => ['title', 'location'],
        ],
        MeetingItem::class => [
            'label' => 'Item de pauta',
            'fields' => ['title', 'notes'],
        ],
        Comment::class => [
            'label' => 'Comentário',
            'fields' => ['text'],
        ],
        Media::class => [
            'label' => 'Arquivo',
            'fields' => ['name', 'original_name', 'uuid'],
        ],
        Link::class => [
            'label' => 'Link',
            'fields' => ['name', 'url'],
        ],
    ];

    private const FIELD_LABELS = [
        'name' => 'Nome',
        'slug' => 'Identificador',
        'title' => 'Título',
        'description' => 'Descrição',
        'text' => 'Texto',
        'notes' => 'Anotações prévias',
        'ata' => 'Ata',
        'status' => 'Status',
        'visibility' => 'Visibilidade',
        'permission_inheritance' => 'Herança de permissões',
        'phase_id' => 'Fase',
        'role' => 'Função',
        'pinned' => 'Fixado',
        'enabled' => 'Ativo',
        'user_id' => 'Usuário',
        'module_id' => 'Módulo',
        'tag_id' => 'Etiqueta',
        'project_id' => 'Projeto',
        'owner_type' => 'Tipo de proprietário',
        'owner_id' => 'Proprietário',
        'url' => 'URL',
        'location' => 'Local',
        'scheduled_at' => 'Agendamento',
        'transcription_length' => 'Tamanho da transcrição',
        'transcription_sha256' => 'Hash da transcrição',
    ];

    public function paginate(Project $project, array $filters): LengthAwarePaginator
    {
        $activities = $this->applyFilters($this->queryFor($project), $filters)
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'activity_page')
            ->withQueryString();

        $activities->getCollection()->transform(
            fn(ActivityLog $activity): array => $this->present($activity),
        );

        return $activities;
    }

    private function queryFor(Project $project): Builder
    {
        $taskIds = Task::withTrashed()
            ->where('project_id', $project->getKey())
            ->select('id');
        $meetingIds = DB::table('meeting_projects')
            ->where('project_id', $project->getKey())
            ->select('meeting_id');

        $projectAndTaskSubjects = [
            $project->getMorphClass() => [$project->getKey()],
            (new Task())->getMorphClass() => $taskIds,
        ];
        $subjects = $projectAndTaskSubjects + [
            (new Meeting())->getMorphClass() => $meetingIds,
        ];

        $meetingItemIds = MeetingItem::query()
            ->where(function (Builder $query) use ($meetingIds, $projectAndTaskSubjects): void {
                $query
                    ->whereIn('meeting_id', $meetingIds)
                    ->orWhere(fn(Builder $query) => $this->whereMorphedToSubjects(
                        $query,
                        'discussable',
                        $projectAndTaskSubjects,
                    ));
            })
            ->select('id');

        $subjects += [
            (new MeetingItem())->getMorphClass() => $meetingItemIds,
            (new Comment())->getMorphClass() => $this->morphedSubjectIds(Comment::class, 'commentable', $subjects),
            (new Media())->getMorphClass() => $this->morphedSubjectIds(Media::class, 'model', $subjects),
            (new Link())->getMorphClass() => $this->morphedSubjectIds(Link::class, 'linkable', $subjects),
        ];

        return ActivityLog::query()->where(
            fn(Builder $query) => $this->whereMorphedToSubjects($query, 'subject', $subjects),
        );
    }

    private function morphedSubjectIds(string $model, string $relation, array $subjects): Builder
    {
        return $model::query()
            ->where(fn(Builder $query) => $this->whereMorphedToSubjects($query, $relation, $subjects))
            ->select('id');
    }

    private function whereMorphedToSubjects(Builder $query, string $relation, array $subjects): Builder
    {
        foreach ($subjects as $type => $ids) {
            $query->orWhere(function (Builder $query) use ($relation, $type, $ids): void {
                $query
                    ->where("{$relation}_type", $type)
                    ->whereIn("{$relation}_id", $ids);
            });
        }

        return $query;
    }

    private function present(ActivityLog $activity): array
    {
        return [
            'record' => $activity,
            'actor' => $activity->causer?->name ?? 'Sistema',
            'action' => self::ACTION_LABELS[$activity->event ?: $activity->description]
                ?? (string) ($activity->event ?: $activity->description),
            'subject' => $this->subjectLabel($activity),
            'changes' => $this->changes($activity),
        ];
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $query
            ->when(
                filled($filters['from'] ?? null),
                fn(Builder $query) => $query->whereDate('created_at', '>=', $filters['from']),
            )
            ->when(
                filled($filters['until'] ?? null),
                fn(Builder $query) => $query->whereDate('created_at', '<=', $filters['until']),
            );

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        $matchedEvents = $this->searchMatches($search, self::EVENT_SEARCH_LABELS);
        $matchedLogNames = $this->searchMatches($search, self::LOG_NAME_SEARCH_LABELS);

        return $query->where(function (Builder $query) use ($search, $like, $matchedEvents, $matchedLogNames): void {
            $query
                ->where('description', 'like', $like)
                ->orWhere('event', 'like', $like)
                ->orWhere('log_name', 'like', $like)
                ->orWhere('subject_type', 'like', $like)
                ->orWhere('subject_id', 'like', $like)
                ->orWhere('causer_type', 'like', $like)
                ->orWhere('causer_id', 'like', $like)
                ->orWhere('properties', 'like', $like)
                ->orWhereHasMorph('causer', [User::class], function (Builder $query) use ($like): void {
                    $query
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('codpes', 'like', $like);
                });

            foreach (self::SUBJECT_SEARCH_FIELDS as $model => $configuration) {
                $query->orWhereHasMorph(
                    'subject',
                    [$model],
                    fn(Builder $subjectQuery): Builder => $this->applySubjectSearch(
                        $subjectQuery,
                        $search,
                        $configuration['label'],
                        $configuration['fields'],
                    ),
                );
            }

            if ($matchedEvents !== []) {
                $query
                    ->orWhereIn('event', $matchedEvents)
                    ->orWhereIn('description', $matchedEvents);
            }

            if ($matchedLogNames !== []) {
                $query->orWhereIn('log_name', $matchedLogNames);
            }
        });
    }

    private function applySubjectSearch(
        Builder $query,
        string $search,
        string $label,
        array $fields,
    ): Builder {
        $like = '%' . $search . '%';

        $query->where(function (Builder $query) use ($like, $fields, $search, $label): void {
            $this->whereAnyLike($query, $fields, $like);

            $labeledSearch = $this->searchTermAfterLabel($search, $label);

            if ($labeledSearch !== null) {
                $query->orWhere($fields[0], 'like', '%' . $labeledSearch . '%');
            }
        });

        return $query;
    }

    private function whereAnyLike(Builder $query, array $fields, string $like): Builder
    {
        foreach ($fields as $index => $field) {
            $query->{$index === 0 ? 'where' : 'orWhere'}($field, 'like', $like);
        }

        return $query;
    }

    private function searchMatches(string $search, array $labelsToValues): array
    {
        $search = Str::lower($search);

        return collect($labelsToValues)
            ->filter(fn(string $value, string $label): bool => Str::contains(Str::lower($label), $search))
            ->values()
            ->all();
    }

    private function searchTermAfterLabel(string $search, string $label): ?string
    {
        $prefix = Str::lower($label) . ':';

        if (! Str::startsWith(Str::lower($search), $prefix)) {
            return null;
        }

        return trim(Str::substr($search, Str::length($prefix)));
    }

    private function subjectLabel(ActivityLog $activity): string
    {
        $subject = $activity->subject;

        return match (true) {
            $subject instanceof Project => 'Projeto: ' . $subject->name,
            $subject instanceof Task => 'Tarefa: ' . $subject->title,
            $subject instanceof Meeting => 'Reunião: ' . $subject->title,
            $subject instanceof MeetingItem => 'Item de pauta',
            $subject instanceof Comment => 'Comentário',
            $subject instanceof Media => 'Arquivo: ' . ($subject->display_name ?: $subject->original_name),
            $subject instanceof Link => 'Link: ' . $subject->display_name,
            default => self::LOG_NAME_LABELS[$activity->log_name] ?? 'Registro',
        };
    }

    private function changes(ActivityLog $activity): array
    {
        $old = $activity->oldValues;
        $new = $activity->newValues;
        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));

        return array_map(function (string|int $key) use ($old, $new): array {
            $key = (string) $key;

            return [
                'field' => self::FIELD_LABELS[$key] ?? Str::headline($key),
                'old' => array_key_exists($key, $old) ? $this->formatValue($old[$key]) : null,
                'new' => array_key_exists($key, $new) ? $this->formatValue($new[$key]) : null,
            ];
        }, $keys);
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return Str::limit(strip_tags((string) $value), 180);
    }
}
