<div class="card-header entity-header entity-header--task h5 py-2 d-flex align-items-center gap-2">
  @if ($project->isModuleEnabled('tasks'))
    <a href="{{ route('projects.tasks.index', $project) }}" class="text-decoration-none text-dark">
      <i class="fas fa-tasks"></i> Tarefas
    </a>
    <x-separator />
  @endif

  @include('project-requests.partials.queue-link')

  @isset($projectRequest)
    <x-separator />
    <span class="text-truncate" title="{{ $projectRequest->title }}">{{ $projectRequest->title }}</span>
    <span class="badge badge-{{ $projectRequest->status->color() }}">{{ $projectRequest->status->label() }}</span>
  @endisset
</div>
