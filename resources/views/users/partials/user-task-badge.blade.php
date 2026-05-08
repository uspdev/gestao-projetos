<span class="badge {{ $project->userRole($user)?->color() ?? 'badge-light border text-muted' }}">
  {{ $project->userRole($user)?->label() ?? 'Sem função' }}
</span>
