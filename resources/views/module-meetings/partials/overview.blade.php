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
            <small class="text-muted d-block mb-2">Projetos vinculados</small>

            @php
              $linkedProjects = $meeting?->projects ?? collect();
            @endphp
            @if ($linkedProjects->isNotEmpty())
              <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                @foreach ($linkedProjects as $linkedProject)
                  <a href="{{ route('projects.show', $linkedProject) }}"
                    class="badge badge-light border text-decoration-none">
                    {{ $linkedProject->name }}
                  </a>
                @endforeach
              </div>
            @else
              <div class="text-muted">Nenhum projeto vinculado.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
