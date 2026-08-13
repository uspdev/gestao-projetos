@can('update', [$meeting, $project])
  @php
    $showOnErrors = $errors->any() && old('_meeting_form') === 'edit';
  @endphp

  <div class="modal fade" id="modalMeetingEdit" tabindex="-1" aria-labelledby="modalMeetingEditLabel"
    aria-hidden="true" data-show-on-errors="{{ $showOnErrors ? '1' : '0' }}">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalMeetingEditLabel">Editar reunião</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          @include('module-meetings.partials.form', [
              'action' => route('projects.meetings.update', [$project, $meeting]),
              'method' => 'PUT',
              'showNotesField' => false,
              'modal' => true,
          ])
        </div>
      </div>
    </div>
  </div>

  @if ($showOnErrors)
    @push('scripts')
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          $('#modalMeetingEdit').modal('show');
        });
      </script>
    @endpush
  @endif
@endcan
