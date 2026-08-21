@php
  $label = 'Subprojetos';
  if (($context ?? null) === 'parent') {
      $project = $project->parent;
      $label = '';
  }
@endphp

<a href="{{ deep_link('projects.show', [$project, 'view' => 'subprojects']) }}"
  class="btn btn-sm {{ request('view') === 'subprojects' ? 'btn-secondary' : 'btn-outline-secondary' }}"
  title="Subprojetos">
  <i class="fas fa-project-diagram"></i> {{ $label }}
</a>
