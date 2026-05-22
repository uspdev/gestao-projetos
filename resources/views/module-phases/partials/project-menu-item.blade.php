@php
  $phase = $project->phase;
@endphp

<span class="badge badge-pill {{ $phase?->color ?? 'badge-light text-dark' }} d-inline-flex align-items-center"
  style="font-size: 0.78rem; padding: 0.3rem 0.65rem; line-height: 1; letter-spacing: 0.01em;" title="Fase do projeto">
  <i class="fas fa-route mr-1" aria-hidden="true"></i>
  {{ $phase?->name ?? 'Nao definido' }}
</span>
