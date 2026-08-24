<section id="user-meetings" class="mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h4 mb-1">
      <i class="fas fa-calendar-alt text-secondary"></i>
      Reuniões
    </div>
  </div>

  <div class="meetings-list meetings-list--dashboard"
    style="max-height: 22rem; overflow-y: auto; overflow-x: hidden; overscroll-behavior-y: auto;"
    tabindex="0" aria-label="Reuniões da dashboard">
    <div class="row">
      @forelse ($meetings as $meeting)
          <div class="col-12 mb-2">
            @include('module-meetings.partials.meeting-card', [
                'compact' => true,
                'showDuplicate' => false,
                'project' => $meeting->contextProjectFor($user, $availableMeetingProjectIds), // Método para obter o projeto de contexto para a reunião
            ])
          </div>
      @empty
        <div class="col-12">
          <div class="alert alert-light border mb-0">
            Sem reunião agendada.
          </div>
        </div>
      @endforelse
    </div>
  </div>
</section>
