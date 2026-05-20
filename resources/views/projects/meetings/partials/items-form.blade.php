@php
  $meetingItems = $meetingItems ?? collect();
  $meetingProjects = $meetingProjects ?? collect();
  $discussableOptions = \App\Morphs\DiscussableMap::options();

  $projectTypeKey = array_search(\App\Models\Project::class, $discussableOptions) ?: 'project';
  $taskTypeKey = array_search(\App\Models\Task::class, $discussableOptions) ?: 'task';

  $defaultType = array_key_exists($taskTypeKey, $discussableOptions)
      ? $taskTypeKey
      : (array_key_first($discussableOptions) ?:
      $taskTypeKey);

  $typeValue = old('discussable_type', $defaultType);
  $nextOrder = (int) ($meetingItems->max('order') ?? 0) + 1;
  $orderValue = old('order', $nextOrder);
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5">
    <i class="fas fa-plus-circle mr-1"></i> Adicionar item de pauta
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('projects.meetings.items.store', [$project, $meeting]) }}">
      @csrf

      <div class="row">
        <div class="col-md-4">
          <div class="form-group mb-3">
            <label for="meeting-item-type">Tipo <span class="text-danger">*</span></label>
            <select name="discussable_type" id="meeting-item-type"
              class="form-control @error('discussable_type') is-invalid @enderror" required>
              @foreach ($discussableOptions as $typeKey => $className)
                <option value="{{ $typeKey }}" {{ $typeValue === $typeKey ? 'selected' : '' }}>
                  {{ $typeKey === 'project' ? 'Projeto' : ($typeKey === 'task' ? 'Tarefa' : ucfirst($typeKey)) }}
                </option>
              @endforeach
            </select>
            @error('discussable_type')
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
              @foreach ($meetingProjects as $meetingProject)
                <option value="{{ $meetingProject->id }}"
                  {{ (string) old('discussable_id') === (string) $meetingProject->id ? 'selected' : '' }}>
                  {{ $meetingProject->name }}
                </option>
                @foreach ($meetingProject->children as $childProject)
                  <option value="{{ $childProject->id }}"
                    {{ (string) old('discussable_id') === (string) $childProject->id ? 'selected' : '' }}>
                    {{ $childProject->name }} (subprojeto)
                  </option>
                @endforeach
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
              @foreach ($meetingProjects as $meetingProject)
                @foreach ($meetingProject->tasks as $task)
                  <option value="{{ $task->id }}"
                    {{ (string) old('discussable_id') === (string) $task->id ? 'selected' : '' }}>
                    {{ $meetingProject->name }} - {{ $task->title }}
                  </option>
                @endforeach
              @endforeach
            </select>
            @error('discussable_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4">
          <x-form.input type="number" name="order" label="Ordem" value="{{ $orderValue }}" min="1"
            required />
        </div>
      </div>

      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Adicionar item
        </button>
      </div>
    </form>
  </div>
</div>

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var typeSelect = document.getElementById('meeting-item-type');
        var projectGroup = document.getElementById('meeting-item-project-group');
        var taskGroup = document.getElementById('meeting-item-task-group');
        var projectSelect = document.getElementById('meeting-item-project');
        var taskSelect = document.getElementById('meeting-item-task');
        var projectType = @json($projectTypeKey);
        var taskType = @json($taskTypeKey);

        if (!typeSelect || !projectGroup || !taskGroup || !projectSelect || !taskSelect) {
          return;
        }

        function toggleDiscussable() {
          var type = typeSelect.value;
          var isProject = type === projectType;
          var isTask = type === taskType;

          projectGroup.style.display = isProject ? '' : 'none';
          taskGroup.style.display = isTask ? '' : 'none';
          projectSelect.disabled = !isProject;
          taskSelect.disabled = !isTask;
        }

        typeSelect.addEventListener('change', toggleDiscussable);
        toggleDiscussable();
      });
    </script>
  @endpush
@endonce
