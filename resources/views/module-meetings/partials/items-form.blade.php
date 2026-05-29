@php
  $meeting = $meeting ?? null;
  $project = $project ?? null;
  $canAdd = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
  $itemsModalId = 'meeting-items-modal-' . ($meeting->id ?? 'new');
@endphp

@if ($canAdd)
  <button type="button" class="btn btn-sm btn-outline-success py-0" data-toggle="modal" data-target="#{{ $itemsModalId }}">
    <i class="fas fa-plus-circle"></i>
  </button>

  <div class="modal fade" id="{{ $itemsModalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $itemsModalId }}-label"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="{{ $itemsModalId }}-label">
            <i class="fas fa-plus-circle mr-1"></i> Adicionar item de pauta
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" action="{{ route('projects.meetings.items.store', [$project, $meeting]) }}">
            @csrf

            <div class="row">
              @include('module-meetings.partials.items-form-discussable')
            </div>

            <div class="row">
              <div class="col-md-4">
                <x-form.input type="number" name="order" label="Ordem" value="{{ $orderValue }}" min="1"
                  required />
              </div>
            </div>

            <div class="d-flex justify-content-end">
              <x-form.cancel-button class="mr-2" data-dismiss="modal" />
              <x-form.save-button class="btn btn-primary" label="Adicionar item" />
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endif
