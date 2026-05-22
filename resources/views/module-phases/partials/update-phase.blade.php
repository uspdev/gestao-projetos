@php
  $phases = $project->projectType?->phases ?? collect();
  $activePhases = $phases->filter(fn($phase) => (bool) $phase->is_active);
  $currentPhaseId = $project->phase_id;
@endphp

@can('update', $project)
  @if ($activePhases->isEmpty())
    <span class="text-muted">Nenhuma fase ativa configurada.</span>
  @else
    <form method="POST" action="{{ route('projects.updatePhase', $project) }}">
      @csrf
      @method('PATCH')
      <div class="input-group">
        <select name="phase_id" class="form-control @error('phase_id') is-invalid @enderror" required>
          @foreach ($activePhases as $phase)
            <option value="{{ $phase->id }}" {{ (string) $currentPhaseId === (string) $phase->id ? 'selected' : '' }}>
              {{ $phase->name }}
            </option>
          @endforeach
        </select>
        <div class="input-group-append">
          <button type="submit" class="btn btn-outline-primary">
            <i class="fas fa-check"></i>
          </button>
        </div>
      </div>
      @error('phase_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    </form>
  @endif
@else
  <span class="btn btn-sm {{ $project->phase?->color ?? 'badge-light text-dark' }}">
    {{ $project->phase?->name ?? 'Nao definido' }}
  </span>
@endcan
