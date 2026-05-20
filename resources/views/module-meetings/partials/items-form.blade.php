<div class="card mb-4 shadow-sm">
  <div class="card-header h5">
    <i class="fas fa-plus-circle mr-1"></i> Adicionar item de pauta
  </div>
  <div class="card-body">
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
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Adicionar item
        </button>
      </div>
    </form>
  </div>
</div>
