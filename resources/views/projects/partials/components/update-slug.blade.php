@can('update', $project)
  <form method="POST" action="{{ route('projects.updateSlug', $project) }}">
    @csrf
    @method('PATCH')
    <div class="input-group">
      <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
        value="{{ old('slug', $project->slug) }}" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
        title="Use apenas letras minusculas, numeros e hifens." autocomplete="off" autocapitalize="none"
        spellcheck="false">
      <div class="input-group-append">
        <button class="btn btn-outline-primary" type="submit" title="Atualizar URL">
          <i class="fas fa-check"></i>
        </button>
      </div>
      @error('slug')
        <div class="invalid-feedback" style="display: block; width: 100%;">{{ $message }}</div>
      @enderror
    </div>
  </form>
  @php
    $hasActiveApiKeys = $project->apiKeys()
        ->whereNull('revoked_at')
        ->where(function ($query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->exists();
    $hasClientSystems = $project->relationLoaded('clientSystems')
        ? $project->clientSystems->isNotEmpty()
        : $project->clientSystems()->exists();
  @endphp
  @if ($hasActiveApiKeys || $hasClientSystems)
    <div data-api-url-warning class="alert alert-warning py-2 mt-2 mb-0" role="alert">
      <strong>Atenção:</strong> Alterar o slug quebrará links antigos, inclusive as URLs da API.
      Os consumidores precisarão atualizar as URLs; a URL anterior deixará de funcionar.
    </div>
  @else
    <small data-api-url-warning class="text-muted d-block mt-1">
      Aviso: Alterar o slug quebrará links antigos, inclusive as URLs da API.
      Os consumidores precisarão atualizar as URLs; a URL anterior deixará de funcionar.
    </small>
  @endif
@else
  <span>{{ $project->slug }}</span>
@endcan
