<section id="user-meetings" class="mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h4 mb-1">
      <i class="fas fa-calendar-alt text-secondary"></i>
      Reuniões agendadas
    </div>
  </div>

  <div class="row">
    @forelse([] as $meeting)
      <div class="col-md-4">
        {{-- @include('projects.partials.components.preview') --}}
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-light border mb-0">
          Você ainda não tem reunião agendada.
        </div>
      </div>
    @endforelse
  </div>
</section>
