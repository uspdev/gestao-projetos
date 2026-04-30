@can('update', $task)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal"
    data-target="#modalEditarTask-{{ $task->id }}">
    <i class="fas fa-edit"></i>
  </button>

  <div class="modal fade" id="modalEditarTask-{{ $task->id }}" tabindex="-1" aria-labelledby="modalEditarTaskLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarTaskLabel">Editar Tarefa: {{ $task->title }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <form action="{{ route('tasks.update', $task->id) }}" method="POST">
          @csrf
          @method('PUT')
          <input type="hidden" name="action" value="{{ url()->current() }}">

          <div class="modal-body">
            {{-- Título --}}
            <div class="row">
              <div class="col-12">
                <x-form.input name="title" label="Título da Tarefa" value="{{ $task->title }}" required />
              </div>
            </div>

            {{-- Classificações (Status, Prioridade e Tags) --}}
            <div class="row">
              <div class="col-md-4">
                <div class="form-group mb-3">
                  <label for="status">Status <span class="text-danger">*</span></label>
                  <select name="status" id="status" class="form-control @error('status') is-invalid @enderror"
                    required>
                    @foreach (\App\Enums\Task\TaskStatus::cases() as $status)
                      <option value="{{ $status->value }}"
                        {{ old('status', $task->status?->value) === $status->value ? 'selected' : '' }}>
                        {{ $status->label() }}
                      </option>
                    @endforeach
                  </select>
                  @error('status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group mb-3">
                  <label for="priority">Prioridade</label>
                  <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror">
                    <option value="">Selecione...</option>
                    @foreach (\App\Enums\Task\TaskPriority::cases() as $priority)
                      <option value="{{ $priority->value }}"
                        {{ old('priority', $task->priority?->value) === $priority->value ? 'selected' : '' }}>
                        {{ $priority->label() }}
                      </option>
                    @endforeach
                  </select>
                  @error('priority')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group mb-3">
                  <label>Tags</label>

                  <select name="tags[]" multiple style="width: 100%;"
                    class="form-control select2-tags @error('tags') is-invalid @enderror @error('tags.*') is-invalid @enderror">

                    @foreach ($availableTaskTags as $tag)
                      @php
                        $currentSelected = collect(old('tags', $tasksSelectedTags[$task->id] ?? []))
                            ->map(fn($id) => (int) $id)
                            ->all();
                      @endphp

                      <option value="{{ $tag->id }}"
                        {{ in_array($tag->id, $currentSelected, true) ? 'selected' : '' }}>
                        {{ $tag->name }}
                      </option>
                    @endforeach
                  </select>

                  @error('tags')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @else
                    @error('tags.*')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  @enderror

                </div>
              </div>
            </div>

            {{-- Datas --}}
            <div class="row">
              <div class="col-md-6">
                <x-form.input type="date" name="start_date" label="Data de Início"
                  value="{{ $task->start_date ? $task->start_date->format('Y-m-d') : '' }}" />
              </div>
              <div class="col-md-6">
                <x-form.input type="date" name="due_date" label="Data de Entrega (Prazo)"
                  value="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}" />
              </div>
            </div>

            {{-- Descrição --}}
            <div class="row">
              <div class="col-12">
                <x-form.textarea name="description" label="Descrição Detalhada" value="{{ $task->description }}"
                  rows="4" />
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar Alterações
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @section('javascripts_bottom')
    @parent
    @include('partials.multi-select-script')
  @endsection
@endcan
