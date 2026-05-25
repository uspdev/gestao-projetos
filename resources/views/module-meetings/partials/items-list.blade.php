@php
  $meetingItems = $meetingItems ?? collect();
  $meeting = $meeting ?? null;
  $project = $project ?? null;
  $canRemove = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
  $canEditNotes = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 py-1 d-flex justify-content-between align-items-center">
    <div>
      <span><i class="fas fa-list-ul mr-1"></i> Itens de pauta</span>
      <span class="badge badge-pill badge-secondary">{{ $meetingItems->count() }}</span>
      @include('module-meetings.partials.items-form')
    </div>
  </div>
  <div class="card-body">
    @if ($meetingItems->isEmpty())
      <div class="text-center text-muted p-4 bg-light rounded border">
        <i class="fas fa-clipboard-list fa-2x mb-3 text-secondary"></i>
        <div class="font-weight-bold mb-1">Nenhum item cadastrado</div>
        <div>Adicione projetos ou tarefas para montar a pauta da reunião.</div>
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
