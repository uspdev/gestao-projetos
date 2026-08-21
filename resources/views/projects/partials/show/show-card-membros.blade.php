@php
  $redirectEditToSettings = $redirectEditToSettings ?? false;
@endphp

<div class="card config-card mb-4">
  <div class="card-header d-flex align-items-center py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-users mr-1" aria-hidden="true"></i> Membros do Projeto
    </h6>
    @unless ($redirectEditToSettings)
      @include('projects.partials.buttons.add-member-btn')
    @endunless
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse($project->users as $user)
        <li id="{{ deep_link_fragment($user) }}" tabindex="-1" data-deep-link-target
          class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
          @include('users.partials.preview')
          <div class="d-flex flex-wrap align-items-center gap-2">
            @include('projects.partials.buttons.member-role-dropdown', [
                'redirectEditToSettings' => $redirectEditToSettings,
            ])
            @unless ($redirectEditToSettings)
              @include('users.partials.remove-member-assignee-btn')
            @endunless
          </div>
        </li>
      @empty
        <li class="list-group-item">Nenhum membro adicionado ao projeto.
      @endforelse
    </ul>
  </div>
</div>
