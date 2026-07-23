<div class="card mb-3">
  <div class="card-header h5 py-2 d-flex justify-content-between align-items-center">
    <span>Informações da reunião</span>

    <div class="d-flex align-items-center" style="gap: 0.5rem;">
      @include('module-meetings.partials.edit-btn')
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
