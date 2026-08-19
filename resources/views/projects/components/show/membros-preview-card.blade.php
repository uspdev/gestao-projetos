<x-projects::show.card-template class="entity-context-card entity-context-card--project">
  <x-slot:header>
    <i class="fas fa-users mr-1"></i> Membros do Projeto
    @include('projects.partials.show.settings-anchor-btn', ['anchor' => 'project-members'])
  </x-slot:header>
  @forelse($project->users as $user)
    <div class="d-flex justify-content-between">
      {{ $user->name }}
      @include('users.partials.user-task-badge-light')
    </div>
  @empty
    <div>Nenhum membro adicionado ao projeto.</div>
  @endforelse
  </x-show-card>
