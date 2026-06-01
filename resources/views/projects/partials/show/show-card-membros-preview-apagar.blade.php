@php
  $redirectEditToSettings = $redirectEditToSettings ?? false;
@endphp

<div class="card mb-4">
  <div class="card-header h6 py-2">
    <i class="fas fa-users mr-1"></i> Membros do Projeto
    @include('projects.partials.show.settings-anchor-btn', ['anchor' => 'project-members'])
  </div>
  <div class="card-body py-2">
    @forelse($project->users as $user)
      <div class="d-flex justify-content-between">
        {{ $user->name }}
        @include('users.partials.user-task-badge-light')
      </div>
    @empty
      <div>Nenhum membro adicionado ao projeto.</div>
    @endforelse
  </div>
</div>
