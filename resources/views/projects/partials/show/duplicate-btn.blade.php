@if ($project->duplicationBlockReason() === null)
  @can('duplicate', $project)
    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal"
      data-target="#duplicate-project-modal-{{ $project->id }}" title="Duplicar projeto" aria-label="Duplicar projeto">
      <i class="fas fa-copy"></i>
    </button>

    @push('modals')
      @include('duplicates.modals.project')
    @endpush
  @endcan
@endif
