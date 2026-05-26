@if (session('tasks_view') === 'kanban')
  <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="btn btn-sm btn-outline-secondary py-0" title="Alterar visualização">
    <i class="fas fa-list"></i>
  </a>
@else
  <a href="{{ request()->fullUrlWithQuery(['view' => 'kanban']) }}" class="btn btn-sm btn-outline-secondary py-0" title="Alterar visualização">
    <i class="fas fa-columns"></i>
  </a>
@endif
