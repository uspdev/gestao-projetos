<span class="small text-{{ $project->userRole($user)?->color() ?? 'muted' }}">
  {{ $project->userRole($user)?->label() ?? 'Sem função' }}
</span>
