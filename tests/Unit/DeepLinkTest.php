<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Link;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeepLinkTest extends TestCase
{
    #[DataProvider('entities')]
    public function test_it_builds_the_canonical_fragment_for_each_navigable_entity(
        object $entity,
        string $expected,
    ): void {
        self::assertSame($expected, deep_link_fragment($entity));
    }

    public static function entities(): array
    {
        return [
            'project' => [self::model(Project::class, 11), 'project-11'],
            'task' => [self::model(Task::class, 12), 'task-12'],
            'meeting' => [self::model(Meeting::class, 13), 'meeting-13'],
            'meeting item' => [self::model(MeetingItem::class, 14), 'meeting-item-14'],
            'comment' => [self::model(Comment::class, 15), 'comment-15'],
            'user' => [self::model(User::class, 16), 'user-16'],
            'file' => [self::model(Media::class, 17, 'file-uuid'), 'file-file-uuid'],
            'link' => [self::model(Link::class, 18, 'link-uuid'), 'link-link-uuid'],
        ];
    }

    public function test_it_points_to_an_explicit_target_different_from_the_route_model(): void
    {
        $task = self::model(Task::class, 12);
        $comment = self::model(Comment::class, 15);

        self::assertSame(
            route('tasks.show', $task).'#comment-15',
            deep_link('tasks.show', $task, target: $comment),
        );
    }

    public function test_it_preserves_query_parameters_before_the_explicit_target(): void
    {
        $project = self::model(Project::class, 11);
        $project->setAttribute('slug', 'demo');
        $comment = self::model(Comment::class, 15);

        self::assertSame(
            route('projects.show', [$project, 'view' => 'subprojects']).'#comment-15',
            deep_link('projects.show', [$project, 'view' => 'subprojects'], target: $comment),
        );
    }

    private static function model(string $class, int $id, ?string $uuid = null): object
    {
        $model = new $class();
        $model->setAttribute($model->getKeyName(), $id);

        if ($uuid !== null) {
            $model->setAttribute('uuid', $uuid);
        }

        return $model;
    }
}
