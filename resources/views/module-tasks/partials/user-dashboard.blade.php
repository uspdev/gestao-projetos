@php
  $tasksDone = session('tasks_done');
@endphp
<section id="user-tasks" class="mb-4">
  <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    <h4 class="mb-0">
      <i class="fas fa-tasks text-secondary"></i>
      Minhas Tarefas
      @if ($tasksDone)
        <x-separator />
        <span class="text-muted">Concluídas</span>
      @endif
    </h4>
    @include('module-tasks.partials.buttons.toggle-layout-btn')
    @include('module-tasks.partials.buttons.show-done-btn')
    @include('module-tasks.partials.buttons.search-task-form')
  </div>

  @if (session('tasks_view') === 'kanban')
    @include('module-tasks.partials.kanban.kanban', ['showDuplicate' => false])
  @else
    <div class="row">
      @forelse($tasksByStatus as $task)
        <div class="col-md-6 col-lg-4 mb-2 task-search-item" data-task-searchable="{{ $task->searchableText() }}">
          <x-task-card :task="$task" :show-duplicate="false" />
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-info">Você ainda não possui tarefas atribuídas.</div>
        </div>
      @endforelse
    </div>
  @endif
  <div class="row mt-3">
    <div class="col-12">
      <div id="tasks-no-results" class="alert alert-info d-none">Nenhuma tarefa encontrada para sua busca.</div>
    </div>
  </div>

</section>
