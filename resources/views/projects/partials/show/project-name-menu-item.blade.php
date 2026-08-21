@php
  if (($context ?? null) === 'parent') {
      $project = $project->parent;
  }
@endphp

<a href="{{ deep_link('projects.show', [$project, 'view' => 'main']) }}"
  class="text-decoration-none  {{ request('view', 'main') === 'main' ? 'text-dark' : 'text-secondary' }}"
  title="Descrição do projeto">
  <span>{{ $project->name }}</span>
</a>
