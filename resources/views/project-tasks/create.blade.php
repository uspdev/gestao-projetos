@php
  $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
@endphp

<button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#modalNovaTask"
  title="Adicionar task">
  <i class="fas fa-plus"></i>
</button>

<div class="modal fade" id="modalNovaTask" tabindex="-1" aria-labelledby="modalNovaTaskLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNovaTaskLabel">Nova Tarefa</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="{{ route('projects.tasks.store', $project) }}" method="POST">
        @csrf
        <div class="modal-body">
          {{-- Título --}}
          <div class="row">
            <div class="col-12">
              <x-form.input name="title" label="Título da Tarefa" required />
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
                    <option value="{{ $status->value }}" {{ old('status') == $status->value ? 'selected' : '' }}>
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
                    <option value="{{ $priority->value }}" {{ old('priority') == $priority->value ? 'selected' : '' }}>
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
                  @foreach ($availableTags as $tag)
                    <option value="{{ $tag->id }}"
                      {{ in_array($tag->id, $selectedTags, true) ? 'selected' : '' }}>
                      {{ $tag->name }}
                    </option>
                  @endforeach
                </select>
                @error('tags')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @error('tags.*')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          {{-- Datas --}}
          <div class="row">
            <div class="col-md-6">
              <x-form.input type="date" name="start_date" label="Data de Início" />
            </div>
            <div class="col-md-6">
              <x-form.input type="date" name="due_date" label="Data de Entrega (Prazo)" />
            </div>
          </div>

          {{-- Descrição --}}
          <div class="row">
            <div class="col-12">
              <x-form.textarea name="description" label="Descrição Detalhada" rows="4" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Criar Tarefa
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('javascripts_bottom')
  @parent
  @include('project-tasks.partials.multi-select-script')

  {{-- Reabrir Modal com Vanilla JS em caso de erro de validação --}}
  @if ($errors->any() && old('title') !== null && old('_method') === null)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const openBtn = document.querySelector('[data-target="#modalNovaTask"]');
        if (openBtn) openBtn.click();
      });
    </script>
  @endif
@endsection
