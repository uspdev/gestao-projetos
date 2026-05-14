@php
  $meeting = $meeting ?? null;
  $method = $method ?? 'POST';
  $statusValue = old('status', $meeting?->status?->value ?? \App\Enums\Meeting\MeetingStatus::SCHEDULED->value);
  $scheduledValue = old('scheduled_at', $meeting?->scheduled_at?->format('Y-m-d\TH:i'));
  $selectedProjects = $selectedProjects ?? old('projects', $meeting?->projects?->pluck('id')->all() ?? [$project->id]);
  $selectedProjects = collect($selectedProjects)->map(fn($id) => (int) $id)->all();
@endphp

<form method="POST" action="{{ $action }}">
  @csrf
  @if ($method !== 'POST')
    @method($method)
  @endif

  <div class="row">
    <div class="col-12">
      <x-form.input name="title" label="Titulo da reuniao" value="{{ old('title', $meeting?->title) }}" required
        minlength="3" maxlength="120" />
    </div>
  </div>

  <div class="row">
    <div class="col-md-4">
      <div class="form-group mb-3">
        <label for="status">Status <span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
          @foreach (\App\Enums\Meeting\MeetingStatus::cases() as $status)
            <option value="{{ $status->value }}" {{ $statusValue === $status->value ? 'selected' : '' }}>
              {{ $status->label() }}
            </option>
          @endforeach
        </select>
        @error('status')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>
    </div>

    <div class="col-md-4">
      <x-form.input type="datetime-local" name="scheduled_at" label="Data e hora" value="{{ $scheduledValue }}" />
    </div>

    <div class="col-md-4">
      <x-form.input name="location" label="Local" value="{{ old('location', $meeting?->location) }}" maxlength="255" />
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <x-form.textarea name="notes" label="Notas" value="{{ old('notes', $meeting?->notes) }}" rows="4"
        maxlength="10000" />
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="form-group mb-3">
        <label for="projects">Projetos vinculados <span class="text-danger">*</span></label>
        <select name="projects[]" id="projects" multiple
          class="form-control @error('projects') is-invalid @enderror @error('projects.*') is-invalid @enderror">
          @foreach ($availableProjects as $availableProject)
            <option value="{{ $availableProject->id }}"
              {{ in_array($availableProject->id, $selectedProjects, true) ? 'selected' : '' }}>
              {{ $availableProject->name }}
            </option>
          @endforeach
        </select>
        @error('projects')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('projects.*')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> Salvar
    </button>
  </div>
</form>
