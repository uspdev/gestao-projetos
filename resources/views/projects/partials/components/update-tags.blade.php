@can('update', $project)
  <form method="POST" action="{{ route('projects.updateTags', $project) }}">
    @csrf
    @method('PATCH')
    <div class="col-12">
      <div class="form-group mb-3">
        <div class="d-flex align-items-start" style="gap: 6px;">
          <div style="width: 40%;">
            <select name="tags[]" multiple
              class="form-control select2-tags @error('tags') is-invalid @enderror">
              @foreach (App\Models\Tag::forProjects() as $tag)
                <option value="{{ $tag->id }}"
                  {{ in_array($tag->id, $project->tags->pluck('id')->toArray(), true) ? 'selected' : '' }}>
                  {{ $tag->name }}
                </option>
              @endforeach
            </select>
          </div>
          <button class="btn btn-outline-primary" type="submit" title="Atualizar Tags">
            <i class="fas fa-check"></i>
          </button>
        </div>
        @error('tags.*')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </form>
  @push('scripts')
    @include('projects.partials.scripts.multi-select-script')
  @endpush
@else
  <span class="text-muted">{{ $project->tags->pluck('name')->join(', ') ?: 'Nenhuma tag' }}</span>
@endcan
