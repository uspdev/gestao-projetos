@canAny(['update', 'updateStatus'], $task)

  <div class="dropdown flex-shrink-0">

    <button class="btn btn-sm p-0 border-0 bg-transparent d-flex align-items-center" type="button" data-toggle="dropdown"
      title="Alterar status da task">
      <span class="d-inline-block {{ $task->status->color() }}" style="width:15px;height:15px;border-radius:50%;"></span>
      <i class="fas fa-chevron-down ml-1 text-muted" style="font-size:.6rem;"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2">
      @foreach (\App\Enums\Task\TaskStatus::cases() as $status)
        <button type="button" class="dropdown-item small task-status-change"
          data-form="task-status-form-{{ $task->id }}" data-status="{{ $status->value }}"
          @disabled($task->status === $status)>
          <span class="badge {{ $status->color() }}" style="font-size: .75rem;">{{ $status->label() }}</span>
          @if ($task->status === $status)
            <small class="text-muted ml-1">(atual)</small>
          @endif
        </button>
      @endforeach
    </div>

    <form id="task-status-form-{{ $task->id }}" method="POST" action="{{ route('tasks.updateTaskStatus', $task) }}"
      class="d-none">
      @csrf
      @method('PATCH')
      <input type="hidden" name="status" value="{{ $task->status->value }}">
    </form>

  </div>
@else
  <span class="badge {{ $task->status->color() }} text-nowrap flex-shrink-0" style="font-size: 0.75rem; line-height: 1;">
    {{ $task->status->label() }}
  </span>

  @pushOnce('scripts')
    <script>
      document.addEventListener('click', function(e) {
        const button = e.target.closest('.task-status-change');
        if (!button) return;
        const form = document.getElementById(button.dataset.form);
        form.querySelector('[name="status"]').value = button.dataset.status;
        form.submit();
      });
    </script>
  @endPushOnce
@endcanAny
