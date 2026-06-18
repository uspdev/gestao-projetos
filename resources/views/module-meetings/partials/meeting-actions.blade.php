@php
  $meetingItems = $meetingItems ?? collect();
  $meeting = $meeting ?? null;
  $project = $project ?? null;
  $canRemove = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
  $canEditNotes = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 py-1 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
      <span class="font-weight-bold">{{ $meeting?->title ?? 'Reuniao' }}</span>

      @if ($meeting)
        @include('module-meetings.partials.status-badge')
        @if ($meetingItems->isNotEmpty())
          <button type="button" id="meeting-notes-toggle-all" class="btn btn-sm btn-outline-secondary py-0"
            aria-expanded="false" title="Expandir anotações" aria-label="Expandir anotações">
            <span class="meeting-notes-toggle-all-icon" aria-hidden="true">
              <i class="far fa-envelope meeting-notes-toggle-all-icon-closed"></i>
              <i class="far fa-envelope-open meeting-notes-toggle-all-icon-open d-none"></i>
            </span>
            <span class="meeting-notes-toggle-all-text sr-only">Expandir anotações</span>
          </button>
        @endif
        @include('module-meetings.partials.items-form')
      @endif
    </div>

    @if ($meeting && $project)
      <div class="d-flex align-items-center" style="gap: 0.5rem;">
        @include('module-meetings.partials.edit-btn')
        @include('module-meetings.partials.delete-btn')
      </div>
    @endif

  </div>
  <div class="card-body">
    @if ($meetingItems->isEmpty())
      <div class="text-center text-muted p-4 bg-light rounded border">
        <i class="fas fa-clipboard-list fa-2x mb-3 text-secondary"></i>
        <div class="font-weight-bold mb-1">Nenhum item cadastrado</div>
        <div>Adicione projetos ou tarefas para montar a pauta da reuniao.</div>
      </div>
    @else
      <ul class="list-group list-group-flush">
        @foreach ($meetingItems as $item)
          @include('module-meetings.partials.items-list-item', [
              'item' => $item,
              'canEditNotes' => $canEditNotes,
          ])
        @endforeach
      </ul>
    @endif
  </div>
</div>
