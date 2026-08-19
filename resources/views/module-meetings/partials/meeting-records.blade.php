<div class="card content-surface entity-context-card entity-context-card--meeting mb-4 shadow-sm" id="meeting-record">
  <div class="card-header d-flex align-items-center py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-clipboard-list mr-1" aria-hidden="true"></i> Registro da reunião
    </h6>
  </div>
  <div class="card-body">
    @foreach ([
        'ata' => ['label' => 'Ata', 'route' => 'projects.meetings.updateAta', 'max' => 10000],
        'transcription' => ['label' => 'Transcrição', 'route' => 'projects.meetings.updateTranscription', 'max' => 100000],
    ] as $field => $record)
      @php
        $content = $meeting->{$field};
        $firstLine = filled($content) ? preg_split('/\R/u', $content, 2)[0] : null;
      @endphp
      <div class="border rounded p-3 meeting-record-item {{ $loop->last ? '' : 'mb-3' }}">
        <div class="d-flex align-items-start justify-content-between">
          <div class="flex-grow-1 min-width-0 mr-2">
            <div class="d-flex align-items-center mb-1" style="gap: 0.25rem;">
              <h6 class="mb-0" id="meeting-{{ $field }}-label">{{ $record['label'] }}</h6>
              @if (filled($content))
                <button type="button" class="btn btn-link btn-sm p-0 text-primary meeting-record-toggle"
                  data-toggle="collapse" data-target="#meeting-{{ $field }}-display" aria-expanded="false"
                  aria-controls="meeting-{{ $field }}-display" title="Alternar {{ $record['label'] }}"
                  aria-label="Alternar {{ $record['label'] }}">
                  <span class="meeting-record-toggle-icon" aria-hidden="true"
                    style="font-size: 1.25rem; font-weight: 700; line-height: 1;">▸</span>
                </button>
              @endif
            </div>
            @if (filled($content))
              <button type="button"
                class="btn btn-link meeting-record-preview d-block w-100 p-0 text-left text-dark text-decoration-none"
                data-toggle="collapse" data-target="#meeting-{{ $field }}-display" aria-expanded="false"
                aria-controls="meeting-{{ $field }}-display"
                aria-label="Expandir {{ $record['label'] }}">
                {{ $firstLine }}
              </button>
            @else
              <small class="text-muted">Sem conteúdo.</small>
            @endif
          </div>
          @can('update', [$meeting, $project])
            <button type="button" class="btn btn-outline-primary btn-sm py-0 meeting-record-edit-toggle"
              data-toggle="collapse" data-display-target="#meeting-{{ $field }}-display"
              data-target="#meeting-{{ $field }}-edit" aria-expanded="false"
              aria-controls="meeting-{{ $field }}-edit"
              aria-label="Editar {{ $record['label'] }}">
              <i class="fas fa-edit"></i>
            </button>
          @endcan
        </div>

        @if (filled($content))
          <div class="collapse mt-2" id="meeting-{{ $field }}-display"
            aria-labelledby="meeting-{{ $field }}-label">
            <div class="meeting-record-content text-dark text-break">{{ $content }}</div>
          </div>
        @endif

        @can('update', [$meeting, $project])
          <div class="collapse" id="meeting-{{ $field }}-edit">
            <form method="POST" action="{{ route($record['route'], [$project, $meeting]) }}" class="mt-2">
              @csrf
              @method('PATCH')
              <div class="d-flex justify-content-end mb-2" style="gap: 0.5rem;">
                <x-form.cancel-button class="btn-sm" data-toggle="collapse"
                  data-target="#meeting-{{ $field }}-edit" />
                <x-form.save-button class="btn btn-primary btn-sm" />
              </div>
              <label for="meeting-{{ $field }}-textarea" class="sr-only">{{ $record['label'] }}</label>
              <textarea name="{{ $field }}" id="meeting-{{ $field }}-textarea"
                class="form-control meeting-record-textarea @error($field) is-invalid @enderror"
                rows="10" maxlength="{{ $record['max'] }}">{{ old($field, $content) }}</textarea>
              @error($field)
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </form>
          </div>
        @endcan
      </div>
    @endforeach
  </div>
</div>

@once
  @push('styles')
    <style>
      .meeting-record-preview {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .meeting-record-preview[aria-expanded="true"] {
        display: none !important;
      }

      .meeting-record-item.is-editing .meeting-record-toggle,
      .meeting-record-item.is-editing .meeting-record-preview,
      .meeting-record-item.is-editing > [id$="-display"] {
        display: none !important;
      }

      .meeting-record-content,
      .meeting-record-textarea {
        height: 16rem;
        max-height: 50vh;
        overflow-y: auto;
      }

      .meeting-record-content {
        white-space: pre-wrap;
      }

      .meeting-record-textarea {
        resize: vertical;
      }
    </style>
  @endpush

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        function syncIcons() {
          document.querySelectorAll('.meeting-record-toggle').forEach(function(button) {
            var icon = button.querySelector('.meeting-record-toggle-icon');
            var expanded = button.getAttribute('aria-expanded') === 'true';
            icon.textContent = expanded ? '▾' : '▸';
          });
        }

        document.querySelectorAll('.meeting-record-toggle, .meeting-record-preview').forEach(function(button) {
          button.addEventListener('click', function() {
            window.setTimeout(syncIcons, 0);
          });
        });

        document.querySelectorAll('.meeting-record-edit-toggle').forEach(function(button) {
          button.addEventListener('click', function() {
            var display = document.querySelector(button.getAttribute('data-display-target'));
            var record = button.closest('.meeting-record-item');

            if (display && display.classList.contains('show')) {
              window.jQuery(display).collapse('hide');
              window.setTimeout(syncIcons, 0);
            }

            if (record) {
              record.classList.toggle('is-editing', button.getAttribute('aria-expanded') !== 'true');
            }
          });
        });

        window.jQuery('.meeting-record-item > [id$="-edit"]').on('hidden.bs.collapse', function() {
          var record = this.closest('.meeting-record-item');

          if (record) {
            record.classList.remove('is-editing');
          }
        });

        syncIcons();
      });
    </script>
  @endpush
@endonce
