<div class="card mb-4 shadow-sm" id="meeting-record">
  <div class="card-header d-flex align-items-center py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-clipboard-list mr-1" aria-hidden="true"></i> Registro da reunião
    </h6>
  </div>
  <div class="card-body">
    @foreach ([
        'ata' => ['label' => 'Ata', 'description' => 'Síntese dos assuntos relevantes e conclusões.', 'route' => 'projects.meetings.updateAta', 'max' => 10000],
        'transcription' => ['label' => 'Transcrição', 'description' => 'Texto bruto produzido externamente.', 'route' => 'projects.meetings.updateTranscription', 'max' => 100000],
    ] as $field => $record)
      <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <div class="d-flex align-items-center" style="gap: 0.25rem;">
              <h6 class="mb-0">{{ $record['label'] }}</h6>
              <button type="button" class="btn btn-link btn-sm p-0 text-primary meeting-record-toggle"
                data-toggle="collapse" data-target="#meeting-{{ $field }}-display" aria-expanded="false"
                aria-controls="meeting-{{ $field }}-display" title="Alternar {{ $record['label'] }}"
                aria-label="Alternar {{ $record['label'] }}">
                <span class="meeting-record-toggle-icon" aria-hidden="true"
                  style="font-size: 1.25rem; font-weight: 700; line-height: 1;">▸</span>
              </button>
            </div>
            <small class="text-muted">{{ $record['description'] }}</small>
          </div>
          @can('update', [$meeting, $project])
            <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
              data-target="#meeting-{{ $field }}-display, #meeting-{{ $field }}-edit"
              aria-label="Editar {{ $record['label'] }}">
              <i class="fas fa-edit"></i>
            </button>
          @endcan
        </div>

        <div class="collapse" id="meeting-{{ $field }}-display">
          @if (filled($meeting->{$field}))
            <div class="text-dark text-break" style="white-space: pre-wrap;">{{ $meeting->{$field} }}</div>
          @else
            <span class="text-muted">-</span>
          @endif
        </div>

        @can('update', [$meeting, $project])
          <div class="collapse" id="meeting-{{ $field }}-edit">
            <form method="POST" action="{{ route($record['route'], [$project, $meeting]) }}" class="mt-2">
              @csrf
              @method('PATCH')
              <label for="meeting-{{ $field }}-textarea" class="sr-only">{{ $record['label'] }}</label>
              <x-form.textarea name="{{ $field }}" id="meeting-{{ $field }}-textarea" :value="$meeting->{$field}"
                groupClass="mb-2" rows="6" maxlength="{{ $record['max'] }}" />
              <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                <x-form.cancel-button class="btn-sm" data-toggle="collapse"
                  data-target="#meeting-{{ $field }}-display, #meeting-{{ $field }}-edit" />
                <x-form.save-button class="btn btn-primary btn-sm" />
              </div>
            </form>
          </div>
        @endcan
      </div>
    @endforeach
  </div>
</div>

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.meeting-record-toggle').forEach(function(button) {
          var icon = button.querySelector('.meeting-record-toggle-icon');

          if (!icon) {
            return;
          }

          function syncIcon() {
            var expanded = button.getAttribute('aria-expanded') === 'true';
            icon.textContent = expanded ? '▾' : '▸';
          }

          button.addEventListener('click', function() {
            window.setTimeout(syncIcon, 0);
          });

          syncIcon();
        });
      });
    </script>
  @endpush
@endonce
