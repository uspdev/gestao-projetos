@php
// Variável $noColor pode ser passada para exibir o badge sem cores,
// usando apenas estilos neutros.
  $noColor = $noColor ?? false;
  $role = $project->userRole($user);
  if ($noColor) {
      $classes = 'badge badge-light border text-muted';
  } else {
      $classes = 'badge badge-' . ($role?->color() ?? 'light');
  }
@endphp
<span class="{{ $classes }}">
  {{ $role?->label() ?? 'Sem função' }}
</span>
