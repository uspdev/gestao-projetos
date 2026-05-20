<span class="badge {{ $meeting->status?->color() ?? 'badge-light text-dark' }}">
  {{ $meeting->status?->label() ?? '-' }}
</span>
