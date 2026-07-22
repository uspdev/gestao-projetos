<div class="dropdown">
  <button type="button" class="btn btn-sm btn-outline-secondary" id="project-actions-dropdown-{{ $project->id }}"
    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Ações do projeto"
    aria-label="Ações do projeto {{ $project->name }}">
    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
  </button>

  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="project-actions-dropdown-{{ $project->id }}">
    <form method="POST" action="{{ route('projects.togglePin', $project) }}">
      @csrf
      @method('PATCH')

      @php($isPinned = $project->isPinnedBy(auth()->user()))

      <button type="submit" class="dropdown-item">
        <i class="fas fa-thumbtack mr-1 {{ $isPinned ? 'text-warning' : '' }}" aria-hidden="true"></i>
        {{ $isPinned ? 'Desafixar projeto' : 'Fixar projeto' }}
      </button>
    </form>

    @if ($project->duplicationBlockReason() === null)
      @can('duplicate', $project)
        <button type="button" class="dropdown-item" data-toggle="modal"
          data-target="#duplicate-project-modal-{{ $project->id }}">
          <i class="fas fa-copy mr-1" aria-hidden="true"></i>
          Duplicar projeto
        </button>

        @push('modals')
          @include('duplicates.modals.project', ['sourceProject' => $project])
        @endpush
      @endcan
    @endif
  </div>
</div>
