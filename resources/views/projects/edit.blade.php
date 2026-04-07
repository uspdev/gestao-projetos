<form action="{{ route('projects.update', $project->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        {{-- Nome --}}
        <div class="col-md-8">
            <x-form.input name="name" label="Nome do Projeto" value="{{ $project->name }}" required />
        </div>

        {{-- Status --}}
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label for="status">Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                    @foreach(\App\Models\ProjectStatus::cases() as $status)
                        <option value="{{ $status->value }}" 
                            {{ old('status', $project->status?->value) === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Descrição --}}
        <div class="col-12">
            <x-form.textarea name="description" label="Descrição Detalhada" value="{{ $project->description }}" rows="4" />
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{-- Botão cancelar --}}
        <button type="button" class="btn btn-outline-secondary mr-2" data-toggle="collapse" data-target="#collapseEditarProjeto">
            Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Alterações
        </button>
    </div>
</form>