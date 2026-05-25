@php
  $href = route('projects.meetings.index', $project);
  $btnClass = str_contains(Route::currentRouteName(), 'projects.meetings') ? 'btn-secondary' : 'btn-outline-secondary';
@endphp

<a href="{{ $href }}" class="btn btn-sm position-relative {{ $btnClass }}">
  <i class="far fa-calendar-alt"></i> Reuniões
  @if ($project->getIncompleteMeetingsCount() > 0)
    <span class="badge badge-pill badge-warning" style="position: absolute; top: -8px; right: -8px;">
      {{ $project->getIncompleteMeetingsCount() }}
    </span>
  @endif
</a>
