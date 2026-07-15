<div class="card mb-4 shadow-sm" id="meeting-record">
  <div class="card-header h5 py-2">Registro da reunião</div>
  <div class="card-body">
    @foreach ([
        'ata' => ['label' => 'Ata', 'description' => 'Síntese dos assuntos relevantes e conclusões.', 'route' => 'projects.meetings.updateAta', 'max' => 10000],
        'transcription' => ['label' => 'Transcrição', 'description' => 'Texto bruto produzido externamente.', 'route' => 'projects.meetings.updateTranscription', 'max' => 100000],
    ] as $field => $record)
      <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <h6 class="mb-0">{{ $record['label'] }}</h6>
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

        <div class="collapse show" id="meeting-{{ $field }}-display">
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
