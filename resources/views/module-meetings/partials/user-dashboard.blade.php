<section id="user-meetings" class="mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h4 mb-1">
      <i class="fas fa-calendar-alt text-secondary"></i>
      Reuniões
    </div>
  </div>

  <div class="row">
    @forelse ($meetings as $meeting)
        <div class="col-12 mb-4">
          @include('module-meetings.partials.index-item', [
              'compact' => true,
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
</section>
