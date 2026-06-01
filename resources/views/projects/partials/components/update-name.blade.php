@can('update', $project)
  <form method="POST" action="{{ route('projects.updateName', $project) }}">
    @csrf
    @method('PATCH')
    <div class="input-group">
      <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $project->name) }}" required minlength="3" maxlength="50">
      <div class="input-group-append">
        <button class="btn btn-outline-primary" type="submit" title="Atualizar nome">
          <i class="fas fa-check"></i>
        </button>
      </div>
      @error('name')
        <div class="invalid-feedback" style="display: block; width: 100%;">{{ $message }}</div>
      @enderror
    </div>
  </form>
@else
  <span>{{ $project->name }}</span>
@endcan
