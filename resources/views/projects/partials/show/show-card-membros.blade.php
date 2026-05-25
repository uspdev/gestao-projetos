<div class="card mb-4">
  <div class="card-header h6 py-2">
    <i class="fas fa-users mr-1"></i> Membros do Projeto
    @include('projects.partials.buttons.add-member-btn')
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse($project->users as $user)
        <li
          class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
          @include('users.partials.preview')
          <div class="d-flex flex-wrap align-items-center gap-2">
            @include('projects.partials.buttons.member-role-dropdown')
            @include('users.partials.remove-member-assignee-btn')
          </div>
        </li>
      @empty
        <li class="list-group-item">Nenhum membro adicionado ao projeto.
      @endforelse
    </ul>
  </div>
</div>
