<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class EntityCardRenderingTest extends TestCase
{
    public function test_layout_exposes_colored_card_dividers_and_shared_shadow(): void
    {
        $response = $this->get(route('about'))->assertOk();

        $response
            ->assertSee('--entity-project-accent: #234983', false)
            ->assertSee('--entity-task-accent: #718596', false)
            ->assertSee('--entity-meeting-accent: #47708D', false)
            ->assertSee('--app-card-meeting-divider: #D5A13A', false)
            ->assertSee('--app-card-settings-divider: #D5A13A', false)
            ->assertSee('--app-card-resources-divider: #6F8799', false)
            ->assertSee('--app-card-shadow: 0 0.25rem 0.75rem rgba(31, 54, 73, 0.12)', false)
            ->assertSee('.card {', false)
            ->assertSee('box-shadow: var(--app-card-shadow) !important', false)
            ->assertSee('.content-surface > .card-header', false)
            ->assertSee('.options-surface > .card-header', false)
            ->assertSee('.file-browser > .card-header', false)
            ->assertSee('.comments-card > .card-header', false)
            ->assertSee('.mentions-card > .card-header', false)
            ->assertSee('.entity-header {', false)
            ->assertSee('.entity-context-card > .card-header', false)
            ->assertSee('.entity-context-card--project', false)
            ->assertSee('.entity-context-card--task', false)
            ->assertSee('.entity-context-card--meeting', false)
            ->assertSee('.config-card > .card-header', false)
            ->assertSee('.meeting-context-card > .card-header', false)
            ->assertSee('border-bottom: 1px solid var(--app-card-meeting-divider) !important', false)
            ->assertSee('border-left: 3px solid var(--entity-accent) !important', false)
            ->assertDontSee('--app-card-blue-header', false)
            ->assertDontSee('--app-card-gray-header', false)
            ->assertDontSee('--app-card-steel-header', false)
            ->assertDontSee('background-color: var(--entity-header-background)', false)
            ->assertDontSee('--entity-project-background', false)
            ->assertDontSee('.entity-card:focus-within', false);
    }

    public function test_list_and_dashboard_cards_render_the_modifier_for_their_entity(): void
    {
        [$project, $task, $meeting] = $this->entities();

        $projectHtml = Blade::render(
            '<x-project-card :project="$project" :show-actions-menu="false" />',
            compact('project'),
        );
        $taskHtml = Blade::render(
            '<x-task-card :task="$task" :show-duplicate="false" />',
            compact('task'),
        );
        $meetingHtml = view('module-meetings.partials.meeting-card', [
            'project' => $project,
            'meeting' => $meeting,
            'compact' => true,
            'showDuplicate' => false,
        ])->render();

        $this->assertStringNotContainsString('entity-card', $projectHtml);
        $this->assertStringContainsString('project-card', $projectHtml);
        $this->assertStringNotContainsString('DodgerBlue', $projectHtml);
        $this->assertStringContainsString('badge badge-success', $projectHtml);

        $this->assertMatchesRegularExpression(
            '/class="[^"]*entity-card entity-card--task[^"]*"/',
            $taskHtml,
        );
        $this->assertStringNotContainsString('card-header', $taskHtml);
        $this->assertStringContainsString('badge badge-warning', $taskHtml);
        $this->assertStringContainsString('badge badge-danger', $taskHtml);
        $this->assertDoesNotMatchRegularExpression(
            '/class="[^"]*task-card[^"]*border-danger[^"]*"/',
            $taskHtml,
        );

        $this->assertMatchesRegularExpression(
            '/class="[^"]*entity-card entity-card--meeting[^"]*"/',
            $meetingHtml,
        );
        $this->assertStringContainsString('badge badge-success', $meetingHtml);
    }

    public function test_kanban_and_subproject_preview_render_the_expected_modifiers(): void
    {
        [$project, $task] = $this->entities();

        $kanbanHtml = view('module-tasks.partials.kanban.kanban-task-card', [
            'task' => $task,
            'showDuplicate' => false,
        ])->render();
        $organizationalProject = new class extends Project
        {
            public Collection $renderedSubprojects;

            public function isOrganizational(): bool
            {
                return true;
            }

            public function subprojects()
            {
                return $this->renderedSubprojects;
            }
        };
        $organizationalProject->forceFill([
            'id' => 101,
            'name' => 'Projeto organizacional',
            'slug' => 'projeto-organizacional',
            'status' => 'ACTIVE',
        ]);
        $organizationalProject->renderedSubprojects = new Collection([$project]);

        $subprojectHtml = view('projects.components.show.subprojects-card', [
            'project' => $organizationalProject,
            'subprojects' => $organizationalProject->renderedSubprojects,
            'type' => 'preview',
        ])->render();

        $this->assertStringNotContainsString('entity-card', $kanbanHtml);
       $this->assertMatchesRegularExpression(
           '/class="[^"]*entity-card entity-card--project[^"]*"/',
           $subprojectHtml,
       );
       $this->assertStringContainsString('entity-context-card entity-context-card--project', $subprojectHtml);
    }

    public function test_watch_dashboard_keeps_neutral_cards_for_each_group(): void
    {
        [$project, $task, $meeting] = $this->entities();
        $watchedResources = collect([
            [
                'type' => 'project',
                'label' => $project->name,
                'context' => null,
                'url' => route('projects.show', $project),
                'resource' => $project,
            ],
            [
                'type' => 'task',
                'label' => $task->title,
                'context' => $project->name,
                'url' => route('tasks.show', $task),
                'resource' => $task,
            ],
            [
                'type' => 'meeting',
                'label' => $meeting->title,
                'context' => $project->name,
                'url' => route('projects.meetings.show', [$project, $meeting]),
                'resource' => $meeting,
            ],
        ]);

        $html = view('watches.partials.user-dashboard', compact('watchedResources'))->render();

        $this->assertStringContainsString('card h-100', $html);
        $this->assertStringContainsString('watch-card watch-card--project', $html);
        $this->assertStringContainsString('watch-card watch-card--task', $html);
        $this->assertStringContainsString('watch-card watch-card--meeting', $html);
    }

    /**
     * @return array{Project, Task, Meeting}
     */
    private function entities(): array
    {
        $projectType = new ProjectType();
        $projectType->forceFill([
            'id' => 10,
            'name' => 'Projeto temático',
            'slug' => 'tematico',
        ]);

        $project = new Project();
        $project->forceFill([
            'id' => 100,
            'name' => 'Projeto visual',
            'slug' => 'projeto-visual',
            'status' => 'ACTIVE',
            'project_type_id' => $projectType->id,
            'parent_id' => null,
        ]);
        $project->setRelation('projectType', $projectType);
        $project->setRelation('tags', new Collection());

        $task = new Task();
        $task->forceFill([
            'id' => 200,
            'project_id' => $project->id,
            'title' => 'Tarefa visual',
            'status' => 'NEW',
            'priority' => 1,
        ]);
        $task->setRelation('project', $project);
        $task->setRelation('tags', new Collection());
        $task->setRelation('users', new Collection());

        $meeting = new Meeting();
        $meeting->forceFill([
            'id' => 300,
            'title' => 'Reunião visual',
            'status' => 'SCHEDULED',
            'scheduled_at' => '2026-08-18 10:00:00',
            'location' => 'Sala de reuniões',
        ]);
        $meeting->setRelation('meetingItems', new Collection());
        $meeting->setRelation('projects', new Collection([$project]));

        return [$project, $task, $meeting];
    }
}
