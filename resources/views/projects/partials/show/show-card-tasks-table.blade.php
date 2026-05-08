{{-- Index: Lista de Tasks --}}
<div class="card mb-4 shadow-sm">
  <div class="card-header h5">
    <i class="fas fa-tasks"></i> Tarefas
    @include('tasks.partials.buttons.create-task-btn')
    @include('tasks.partials.buttons.toggle-layout-btn')
    @include('tasks.partials.buttons.show-done-btn')
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered datatable-simples">
        <thead>
          <tr>
            <th></th>
            <th title="Prioridade">Prio.</th>
            <th>Status</th>
            <th>Início</th>
            <th>Prazo</th>
            <th>Título</th>
            <th>Responsável</th>
            <th>Tags</th>

          </tr>
        </thead>
        <tbody>
          @foreach ($tasks as $task)
            <tr>
              <td>
                @include('tasks.partials.buttons.edit-btn')
              </td>
              <td>
                <span class="badge {{ $task->priority?->color() }}">{{ $task->priority?->label() }}</span>
              </td>
              <td>
                <span class="badge {{ $task->status->color() }}">{{ $task->status->label() }}</span>
              </td>
              <td data-order="{{ $task->start_date?->format('Y-m-d H:i:s') }}">
                <x-local-date :date="$task->start_date" empty="-" />
              </td>
              <td data-order="{{ $task->due_date?->format('Y-m-d H:i:s') }}">
                <x-local-date :date="$task->due_date" :overdue="$task->isOverdue()" empty="-" />
              </td>
              <td>
                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none">
                  {{ $task->title }}
                </a>
              </td>
              <td>
                {{ $task->users->pluck('name')->implode(', ') }}
              </td>
              <td>
                @foreach ($task->tags as $tag)
                  <span class="badge badge-secondary">{{ $tag->name }}</span>
                @endforeach
              </td>

            </tr>
          @endforeach
        </tbody>
      </table>
      {{-- @dd($tasks) --}}
    </div>
  </div>
</div>
