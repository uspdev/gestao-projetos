<?php

namespace App\Services\Files;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class ProjectFileVisibility
{
    /**
     * Monta a consulta dos Arquivos acessíveis pelo Projeto por propriedade ou
     * compartilhamento, considerando apenas caminhos habilitados e ativos.
     *
     * @return Builder<Media>
     */
    public function query(Project $project): Builder
    {
        $tasksEnabled = $project->isModuleEnabled('tasks');
        $meetingsEnabled = $project->isModuleEnabled('meetings');

        return Media::query()
            ->where(function (Builder $query) use ($project, $tasksEnabled, $meetingsEnabled): void {
                $query->where(fn (Builder $projectFiles): Builder => $projectFiles
                    ->where('model_type', $project->getMorphClass())
                    ->where('model_id', $project->getKey()));

                if ($tasksEnabled) {
                    $query->orWhere(fn (Builder $taskFiles): Builder => $taskFiles
                        ->where('model_type', (new Task())->getMorphClass())
                        ->whereHasMorph(
                            'model',
                            Task::class,
                            fn (Builder $tasks): Builder => $tasks->where('project_id', $project->getKey()),
                        ));
                }

                if ($meetingsEnabled) {
                    $query
                        ->orWhere(fn (Builder $meetingFiles): Builder => $meetingFiles
                            ->where('model_type', (new Meeting())->getMorphClass())
                            ->whereHasMorph(
                                'model',
                                Meeting::class,
                                fn (Builder $meetings): Builder => $meetings->whereHas(
                                    'projects',
                                    fn (Builder $projects): Builder => $projects->whereKey($project->getKey()),
                                ),
                            ))
                        ->orWhereHas(
                            'sharedWithMeetings',
                            fn (Builder $meetings): Builder => $meetings->whereHas(
                                'projects',
                                fn (Builder $projects): Builder => $projects->whereKey($project->getKey()),
                            ),
                        );
                }
            });
    }
}
