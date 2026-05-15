<div class="card mb-4">
  <div
    class="card-header d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between py-2"
    style="gap: 0.5rem;">
    <div class="h5 mb-0">
      <i class="fas fa-users mr-1"></i> Membros do Projeto
    </div>
    @include('projects.partials.buttons.add-member-btn')
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse($project->users as $user)
        <li
          class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center"
          style="gap: 0.75rem;">
          @include('users.partials.preview')
          <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
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
