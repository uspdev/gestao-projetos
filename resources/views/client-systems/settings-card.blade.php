@php
  $editingClientSystem = old('_client_system_form');
@endphp

<section id="project-integrations-settings" class="card config-card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between py-2">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-plug mr-1" aria-hidden="true"></i> Integrações
      </h6>
      <button class="btn btn-sm btn-outline-success py-0" type="button" data-toggle="collapse"
        data-target="#client-system-create" aria-expanded="{{ $editingClientSystem === 'create' ? 'true' : 'false' }}"
        aria-controls="client-system-create">
        <i class="fas fa-plus mr-1" aria-hidden="true"></i> Novo Sistema cliente
      </button>
    </div>
  </div>

  <div id="client-system-create" class="collapse {{ $editingClientSystem === 'create' ? 'show' : '' }}">
    <div class="card-body border-bottom">
      <form method="POST" action="{{ route('projects.client-systems.store', $project) }}">
        @csrf
        <input type="hidden" name="_client_system_form" value="create">
        <div class="form-group">
          <label for="client-system-create-name">Nome</label>
          <input id="client-system-create-name" name="name" type="text"
            class="form-control @if($editingClientSystem === 'create' && $errors->has('name')) is-invalid @endif"
            value="{{ $editingClientSystem === 'create' ? old('name') : '' }}" minlength="3" maxlength="120" required>
          @if ($editingClientSystem === 'create')
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          @endif
        </div>
        <div class="form-group">
          <label for="client-system-create-description">Descrição <span class="text-muted">(opcional)</span></label>
          <textarea id="client-system-create-description" name="description" rows="3" maxlength="1000"
            class="form-control @if($editingClientSystem === 'create' && $errors->has('description')) is-invalid @endif">{{ $editingClientSystem === 'create' ? old('description') : '' }}</textarea>
          @if ($editingClientSystem === 'create')
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
          @endif
        </div>
        <button class="btn btn-primary" type="submit">Criar Sistema cliente</button>
      </form>
    </div>
  </div>

  @if ($project->clientSystems->isEmpty())
    <div class="card-body text-muted">
      Nenhum Sistema cliente cadastrado neste Projeto.
    </div>
  @else
    <div class="list-group list-group-flush">
      @foreach ($project->clientSystems as $clientSystem)
        @php
          $editFormId = 'client-system-edit-'.$clientSystem->id;
          $isEditing = (string) $editingClientSystem === (string) $clientSystem->id;
        @endphp
        <div class="list-group-item px-3 py-3">
          <div class="d-flex align-items-start justify-content-between">
            <div class="pr-3">
              <h6 class="mb-1">{{ $clientSystem->name }}</h6>
              @if ($clientSystem->description)
                <p class="mb-0 text-muted">{!! nl2br(e($clientSystem->description)) !!}</p>
              @else
                <p class="mb-0 text-muted"><em>Sem descrição.</em></p>
              @endif
            </div>
            <div class="d-flex align-items-center flex-wrap justify-content-end" style="gap: .35rem;">
              <a class="btn btn-sm btn-outline-primary"
                href="{{ route('projects.client-systems.api-keys', [$project, $clientSystem]) }}">
                Gerenciar chaves
              </a>
              <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse"
                data-target="#{{ $editFormId }}" aria-expanded="{{ $isEditing ? 'true' : 'false' }}"
                aria-controls="{{ $editFormId }}">
                Editar
              </button>
            </div>
          </div>

          <div id="{{ $editFormId }}" class="collapse {{ $isEditing ? 'show' : '' }}">
            <form class="border-top mt-3 pt-3" method="POST"
              action="{{ route('projects.client-systems.update', [$project, $clientSystem]) }}">
              @csrf
              @method('PATCH')
              <input type="hidden" name="_client_system_form" value="{{ $clientSystem->id }}">
              <div class="form-group">
                <label for="client-system-{{ $clientSystem->id }}-name">Nome</label>
                <input id="client-system-{{ $clientSystem->id }}-name" name="name" type="text"
                  class="form-control @if($isEditing && $errors->has('name')) is-invalid @endif"
                  value="{{ $isEditing ? old('name') : $clientSystem->name }}" minlength="3" maxlength="120" required>
                @if ($isEditing)
                  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @endif
              </div>
              <div class="form-group">
                <label for="client-system-{{ $clientSystem->id }}-description">Descrição <span class="text-muted">(opcional)</span></label>
                <textarea id="client-system-{{ $clientSystem->id }}-description" name="description" rows="3" maxlength="1000"
                  class="form-control @if($isEditing && $errors->has('description')) is-invalid @endif">{{ $isEditing ? old('description') : $clientSystem->description }}</textarea>
                @if ($isEditing)
                  @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @endif
              </div>
              <button class="btn btn-primary" type="submit">Salvar alterações</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</section>
