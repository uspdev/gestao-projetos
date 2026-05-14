@can('update', $project)
  <form method="POST" action="{{ route('projects.updateSlug', $project) }}" class="form-inline">
    @csrf
    @method('PATCH')
    <div class="input-group" style="width: 30%;">
      <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
        value="{{ old('slug', $project->slug) }}" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
        title="Use apenas letras minusculas, numeros e hifens." autocomplete="off" autocapitalize="none"
        spellcheck="false" style="flex: 1;">
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
  <small class="text-muted d-block mt-1">Aviso: Alterar a URL quebrará links antigos já compartilhados.</small>
@else
  <span>{{ $project->slug }}</span>
@endcan
