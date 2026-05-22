<?php

namespace App\Listeners;

use App\Models\Meeting;
use App\Models\MeetingProject;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectType;
use App\Models\ProjectTypeModule;
use App\Models\ProjectUser;
use App\Models\Task;
use App\Models\TaskUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;

/**
 * Subscriber responsável exclusivamente por auditar updateExistingPivot().
 *
 * Os eventos created/deleted de pivots são tratados nos próprios models via
 * booted() — cada pivot é autossuficiente para seu ciclo de vida básico.
 *
 * Este subscriber existe porque updateExistingPivot() dispara eloquent.updated
 * no pivot mas não passa pelo booted() de forma confiável e centralizar esse caso
 * aqui evita duplicação nos models.
 *
 * Pivots cobertos e seus owners:
 *   ProjectUser      → Project   (cobre role e pinned via updateExistingPivot)
 *   TaskUser         → Task
 *   MeetingProject   → Meeting
 *   ProjectModule    → Project
 *   ProjectTypeModule → ProjectType
 */
class PivotAuditSubscriber
{
    private const PIVOT_MAP = [
        ProjectUser::class => [
            'owner'     => Project::class,
            'owner_key' => 'project_id',
            'log_name'  => 'project',
        ],
        TaskUser::class => [
            'owner'     => Task::class,
            'owner_key' => 'task_id',
            'log_name'  => 'task',
        ],
        MeetingProject::class => [
            'owner'     => Meeting::class,
            'owner_key' => 'meeting_id',
            'log_name'  => 'meeting',
        ],
        ProjectModule::class => [
            'owner'     => Project::class,
            'owner_key' => 'project_id',
            'log_name'  => 'project',
        ],
        ProjectTypeModule::class => [
            'owner'     => ProjectType::class,
            'owner_key' => 'project_type_id',
            'log_name'  => 'project_type',
        ],
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach (array_keys(self::PIVOT_MAP) as $pivotClass) {
            $events->listen("eloquent.updated: {$pivotClass}", [$this, 'handleUpdated']);
        }
    }

    public function handleUpdated(Model $pivot): void
    {
        $config = self::PIVOT_MAP[$pivot::class] ?? null;

        if (! $config) {
            return;
        }

        $changes = $this->filterAttributes($pivot->getChanges(), $pivot);

        // Nenhum campo auditável foi alterado —> não grava log vazio.
        if (empty($changes)) {
            return;
        }

        $original = Arr::only($pivot->getOriginal(), array_keys($changes));
        $owner    = $this->resolveOwner($config['owner'], $pivot->getAttribute($config['owner_key']));

        if (! $owner) {
            return;
        }

        // Lê o log_name do getActivitylogOptions() do pivot se disponível, caindo no PIVOT_MAP como fallback.
        $logName = $config['log_name'];
        if (method_exists($pivot, 'getActivitylogOptions')) {
            $options = $pivot->getActivitylogOptions();
            $logName = $options->logName ?? $logName;
        }

        activity()
            ->useLog($logName)
            ->event('updated')
            ->performedOn($owner)
            ->withProperties([
                'attributes' => $changes,
                'old'        => $original,
            ])
            ->log('updated');
    }

    /**
     * Filtra os atributos alterados para incluir apenas os definidos em
     * getActivitylogOptions()->logAttributes, se o pivot implementar o método.
     * Pivots sem o método têm todos os seus campos alterados registrados.
     */
    private function filterAttributes(array $attributes, Model $pivot): array
    {
        if (! method_exists($pivot, 'getActivitylogOptions')) {
            return $attributes;
        }

        $options       = $pivot->getActivitylogOptions();
        $logAttributes = $options->logAttributes ?? [];

        if (empty($logAttributes) || in_array('*', $logAttributes, true)) {
            return $attributes;
        }

        return Arr::only($attributes, $logAttributes);
    }

    private function resolveOwner(string $class, mixed $id): ?Model
    {
        if (! $id) {
            return null;
        }

        $query = $class::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($id);
    }
}