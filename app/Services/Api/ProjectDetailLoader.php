<?php

namespace App\Services\Api;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProjectDetailLoader
{
    public function __construct(private ProjectIncomingMentions $incomingMentions)
    {
    }

    public function loadProject(Project $project): Project
    {
        $project->load([
            'projectType.modules',
            'phase',
            'parent',
            'tags',
            'users',
        ]);
        $this->loadCommon($project, $project);

        $agendaMeetings = collect();
        if (
            $project->isModuleEnabled('meetings')
            && Schema::hasTable('meeting_items')
            && Schema::hasTable('meetings')
            && Schema::hasTable('meeting_projects')
        ) {
            $agendaMeetings = $project->meetingItems()
                ->with('meeting.projects')
                ->orderBy('order')
                ->get()
                ->map(fn ($item) => $item->meeting)
                ->filter(fn (?Meeting $meeting): bool => $meeting?->projects
                    ->contains(fn (Project $linked): bool => (int) $linked->getKey() === (int) $project->getKey()) ?? false)
                ->unique(fn (Meeting $meeting): int => (int) $meeting->getKey())
                ->values();
        }
        $project->setRelation('apiAgendaMeetings', $agendaMeetings);

        $project->setRelation(
            'apiSubprojects',
            Schema::hasTable('projects') ? $project->subprojects() : collect(),
        );

        return $project;
    }

    public function loadTask(Task $task, Project $project): Task
    {
        $task->load([
            'project.users',
            'tags',
            'users' => fn ($query) => $query->orderBy('name'),
        ]);
        $this->loadCommon($task, $project);

        return $task;
    }

    public function loadMeeting(Meeting $meeting, Project $project): Meeting
    {
        $meeting->load([
            'projects' => fn ($query) => $query->orderBy('name')->orderBy('id'),
            'meetingItems' => fn ($query) => $query->with('discussable')->orderBy('order')->orderBy('id'),
        ]);
        $this->loadCommon($meeting, $project, shared: true);

        return $meeting;
    }

    private function loadCommon(Model $owner, Project $project, bool $shared = false): void
    {
        $comments = Schema::hasTable('comments')
            ? $owner->comments()->active()->with('user')->orderBy('created_at')->orderBy('id')->get()
            : new EloquentCollection();
        $ownedFiles = Schema::hasTable('media')
            ? $owner->media()->with('model')->orderByDesc('created_at')->orderByDesc('id')->get()
            : new EloquentCollection();
        $ownedLinks = Schema::hasTable('links')
            ? $owner->links()->with('linkable')->orderByDesc('created_at')->orderByDesc('id')->get()
            : new EloquentCollection();

        $sharedFiles = new EloquentCollection();
        $sharedLinks = new EloquentCollection();
        if ($shared && $owner instanceof Meeting) {
            if (Schema::hasTable('meeting_file_shares')) {
                $sharedFiles = $owner->sharedFiles()
                    ->with('model')
                    ->orderByDesc('meeting_file_shares.created_at')
                    ->get()
                    ->reject(fn ($file): bool => $ownedFiles->contains('id', $file->id))
                    ->values();
            }
            if (Schema::hasTable('meeting_link_shares')) {
                $sharedLinks = $owner->sharedLinks()
                    ->with('linkable')
                    ->orderByDesc('meeting_link_shares.created_at')
                    ->get()
                    ->reject(fn ($link): bool => $ownedLinks->contains('id', $link->id))
                    ->values();
            }
        }

        $owner->setRelation('apiComments', $comments);
        $owner->setRelation('apiOwnedFiles', $ownedFiles);
        $owner->setRelation('apiSharedFiles', $sharedFiles);
        $owner->setRelation('apiOwnedLinks', $ownedLinks);
        $owner->setRelation('apiSharedLinks', $sharedLinks);
        $owner->setRelation('apiIncomingMentions', $this->incomingMentions->for($owner, $project));
    }
}
