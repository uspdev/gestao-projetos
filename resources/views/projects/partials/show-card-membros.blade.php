{{-- Membros do Projeto --}}
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h6 class="m-0 text-muted">
      <i class="fas fa-users mr-1"></i> Membros do Projeto
    </h6>
    @includeWhen(auth()->user()->can('storeMember', $project), 'partials.projects.add-member', [
        'project' => $project,
    ])
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
