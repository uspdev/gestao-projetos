<div class="col-md-4">
  <div class="form-group mb-3">
    <label for="meeting-item-type">Tipo <span class="text-danger">*</span></label>
    <select name="item_type" id="meeting-item-type"
      class="form-control @error('item_type') is-invalid @enderror" required>
      @foreach ($discussableOptions as $typeKey => $className)
        <option value="{{ $typeKey }}" {{ $typeValue === $typeKey ? 'selected' : '' }}>
          {{ $typeKey === 'project' ? 'Projeto' : ($typeKey === 'task' ? 'Tarefa' : ucfirst($typeKey)) }}
        </option>
      @endforeach
      <option value="{{ $independentTypeKey }}" {{ $typeValue === $independentTypeKey ? 'selected' : '' }}>
        Item independente
      </option>
    </select>
    @error('item_type')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="col-md-8">
  <div class="form-group mb-3" id="meeting-item-title-group"
    @if ($typeValue !== $independentTypeKey) style="display:none;" @endif>
    <label for="meeting-item-title">Título do item <span class="text-danger">*</span></label>
    <input type="text" name="title" id="meeting-item-title"
      class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" maxlength="255"
      @if ($typeValue !== $independentTypeKey) disabled @else required @endif>
    @error('title')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="col-md-8">
  <div class="form-group mb-3" id="meeting-item-project-group"
    @if ($typeValue !== $projectTypeKey) style="display:none;" @endif>
    <label for="meeting-item-project">Projeto <span class="text-danger">*</span></label>
    <select name="discussable_id" id="meeting-item-project"
      class="form-control @error('discussable_id') is-invalid @enderror"
      @if ($typeValue !== $projectTypeKey) disabled @endif>
      <option value="">Selecione...</option>
      @foreach ($projectOptions as $option)
        <option value="{{ $option['value'] }}"
          {{ (string) old('discussable_id') === (string) $option['value'] ? 'selected' : '' }}>
          {{ $option['label'] }}
        </option>
      @endforeach
    </select>
    @error('discussable_id')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  <div class="form-group mb-3" id="meeting-item-task-group"
    @if ($typeValue !== $taskTypeKey) style="display:none;" @endif>
    <label for="meeting-item-task">Tarefa <span class="text-danger">*</span></label>
    <select name="discussable_id" id="meeting-item-task"
      class="form-control @error('discussable_id') is-invalid @enderror"
      @if ($typeValue !== $taskTypeKey) disabled @endif>
      <option value="">Selecione...</option>
      @foreach ($taskOptions as $option)
        <option value="{{ $option['value'] }}"
          {{ (string) old('discussable_id') === (string) $option['value'] ? 'selected' : '' }}>
          {{ $option['label'] }}
        </option>
      @endforeach
    </select>
    @error('discussable_id')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>
</div>

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var typeSelect = document.getElementById('meeting-item-type');
        var projectGroup = document.getElementById('meeting-item-project-group');
        var taskGroup = document.getElementById('meeting-item-task-group');
        var titleGroup = document.getElementById('meeting-item-title-group');
        var projectSelect = document.getElementById('meeting-item-project');
        var taskSelect = document.getElementById('meeting-item-task');
        var titleInput = document.getElementById('meeting-item-title');
        var projectType = @json($projectTypeKey);
        var taskType = @json($taskTypeKey);
        var independentType = @json($independentTypeKey);

        if (!typeSelect || !projectGroup || !taskGroup || !titleGroup || !projectSelect || !taskSelect || !titleInput) {
          return;
        }

        function toggleDiscussable() {
          var type = typeSelect.value;
          var isProject = type === projectType;
          var isTask = type === taskType;
          var isIndependent = type === independentType;

          projectGroup.style.display = isProject ? '' : 'none';
          taskGroup.style.display = isTask ? '' : 'none';
          titleGroup.style.display = isIndependent ? '' : 'none';
          projectSelect.disabled = !isProject;
          taskSelect.disabled = !isTask;
          titleInput.disabled = !isIndependent;
          titleInput.required = isIndependent;
        }

        typeSelect.addEventListener('change', toggleDiscussable);
        toggleDiscussable();
      });
    </script>
  @endpush
@endonce
