{{-- Membros do Projeto --}}
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-users mr-1"></i> Membros do Projeto
      </h6>
      @includeWhen(auth()->user()->can('storeMember', $project), 'projects.partials.add-member-btn')
    </div>
  </div>
  <ul class="list-group list-group-flush">
    @forelse($project->users as $user)
      @include('users.preview', [
          'user' => $user,
          'project' => $project,
          'canManageMembers' => auth()->user()->can('storeMember', $project),
      ])
    @empty
      <li class="list-group-item text-muted font-italic small text-center py-3">
        Nenhum membro vinculado.
      </li>
    @endforelse
  </ul>
</div>
