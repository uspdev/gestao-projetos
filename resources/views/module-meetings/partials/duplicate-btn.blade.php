@can('duplicate', [$meeting, $project])
  <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-toggle="modal"
    data-target="#duplicate-meeting-modal-{{ $meeting->id }}" title="Duplicar reunião" aria-label="Duplicar reunião">
    <i class="fas fa-copy"></i>
  </button>

  @push('modals')
    @include('duplicates.modals.meeting')
  @endpush
@endcan
