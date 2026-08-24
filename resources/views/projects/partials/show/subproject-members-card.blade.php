@forelse ($project->children as $subproject)
  @php
    $memberCount = $subproject->users->count();
    $inheritance = $subproject->permission_inheritance;
  @endphp

  <div class="card border entity-context-card entity-context-card--project mb-3"
    data-subproject-members-searchable="{{ $subproject->name }} {{ $subproject->users->pluck('name')->implode(' ') }}">
    <div class="card-header bg-light d-flex flex-column flex-md-row align-items-start align-items-md-center">
      <div class="mb-2 mb-md-0 d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
        <a href="{{ route('projects.show', $subproject) }}" class="font-weight-bold text-dark">
          <i class="fas fa-folder mr-1 text-secondary" aria-hidden="true"></i>
          {{ $subproject->name }}
        </a>
        <span class="small text-muted">
          {{ $memberCount }} {{ $memberCount === 1 ? 'membro direto' : 'membros diretos' }}
        </span>
        <span class="badge badge-{{ $inheritance?->color() ?? 'secondary' }}">
          {{ $inheritance?->label() ?? 'Herança não definida' }}
        </span>

        @can('storeMember', $subproject)
          <a href="{{ route('projects.settings', $subproject) }}#project-members"
            class="btn btn-sm btn-outline-primary">
            Gerenciar equipe
          </a>
        @endcan
      </div>
    </div>

    <ul class="list-group list-group-flush">
      @forelse ($subproject->users as $member)
        @php
          $role = $subproject->userRole($member);
        @endphp

        <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
          <div class="mb-2 mb-md-0">
            <i class="fas fa-user-circle text-secondary mr-1" aria-hidden="true"></i>
            <a href="{{ route('users.show', $member) }}" class="text-dark">
              {{ $member->name }}
            </a>
          </div>

          <span class="badge badge-{{ $role?->color() ?? 'light' }}">
            {{ $role?->label() ?? 'Sem função' }}
          </span>
        </li>
      @empty
        <li class="list-group-item text-muted">
          Nenhum membro vinculado diretamente a este subprojeto.
        </li>
      @endforelse
    </ul>
  </div>
@empty
  <p class="text-muted mb-0">Este projeto organizacional ainda não possui subprojetos.</p>
@endforelse
