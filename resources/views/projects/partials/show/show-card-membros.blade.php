<div class="card mb-4">
  <div class="card-header h5 py-2">
    <i class="fas fa-users mr-1"></i> Membros do Projeto
    @include('projects.partials.buttons.add-member-btn')
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse($project->users as $user)
        <li class="list-group-item d-flex gap-2 justify-content-between align-items-center">
          @include('users.partials.preview')
        </li>
      @empty
        <li class="list-group-item">Nenhum membro adicionado ao projeto.
      @endforelse
    </ul>
  </div>
</div>
