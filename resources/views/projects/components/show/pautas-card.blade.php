@props(['project', 'meetings'])

<x-projects::show.card-template>
  <x-slot:header>
    <div class="d-flex align-items-center justify-content-between">
      <span><i class="fas fa-list-ol mr-1" aria-hidden="true"></i> Pautas de reuniões</span>
      <span class="badge badge-pill badge-secondary">{{ $meetings->count() }}</span>
    </div>
  </x-slot:header>

  @foreach ($meetings as $meeting)
    <div class="@unless($loop->last) mb-3 @endunless">
      <a href="{{ route('projects.meetings.show', [$project, $meeting]) }}" class="font-weight-bold text-dark">
        {{ $meeting->title }}
      </a>
      <div class="small text-muted">
        <span class="mr-2">Projeto na pauta</span>
        <x-local-date :date="$meeting->scheduled_at" empty="sem data" />
      </div>
    </div>
  @endforeach
</x-projects::show.card-template>
