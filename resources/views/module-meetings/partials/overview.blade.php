<div class="card mb-3">
  <div class="card-body">
    <div class="row">
      <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <small class="text-muted d-block">Data e hora</small>
              <strong>
                <x-local-date :date="$meeting->scheduled_at" empty="-" />
              </strong>
            </div>

            <div class="mb-3 mb-md-0">
              <small class="text-muted d-block">Local</small>
              <strong>{{ $meeting->location ?? '-' }}</strong>
            </div>
          </div>

          <div class="col-md-6">
            @include('module-meetings.partials.show/projetos-vinculados')
          </div>
        </div>
      </div>
    </div>
    <div>
      <small class="text-muted d-block">Notas</small>
      {{ $meeting->notes }}
    </div>
  </div>
</div>
