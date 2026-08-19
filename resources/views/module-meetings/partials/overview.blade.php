<div class="card options-surface entity-context-card entity-context-card--meeting mb-3">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Informações da reunião
    </h6>

    <div class="d-flex align-items-center" style="gap: 0.5rem;">
      @include('watches.partials.control', ['watchable' => $meeting])
      @include('module-meetings.partials.delete-btn')
    </div>
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <small class="text-muted d-block">Data e hora</small>
        <strong>
          <x-local-date :date="$meeting->scheduled_at" :show-time="true" empty="-" />
        </strong>
      </li>

      <li class="list-group-item">
        <small class="text-muted d-block">Local</small>
        <strong>{{ $meeting->location ?? '-' }}</strong>
      </li>

      <li class="list-group-item p-0">
        @include('module-meetings.partials.show.projetos-vinculados')
      </li>

    </ul>
  </div>
</div>
