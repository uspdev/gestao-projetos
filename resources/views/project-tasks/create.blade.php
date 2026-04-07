<form action="{{ route('projects.tasks.store', $project_id) }}" method="POST">
    @csrf

    {{-- Título --}}
    <div class="row">
        <div class="col-12">
            <x-form.input name="title" label="Título da Tarefa" required />
        </div>
    </div>

    {{-- Classificações (Status, Prioridade e Label) --}}
    <div class="row">
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label for="status">Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                    @foreach(\App\Models\TaskStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ old('status') == $status->value ? 'selected' : '' }}>
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
            <div class="form-group mb-3">
                <label for="priority">Prioridade</label>
                <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror">
                    <option value="">Selecione...</option>
                    @foreach(\App\Models\TaskPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" {{ old('priority') == $priority->value ? 'selected' : '' }}>
                            {{ $priority->label() }}
                        </option>
                    @endforeach
                </select>
                @error('priority')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group mb-3">
                <label for="label">Label (Tag)</label>
                <select name="label" id="label" class="form-control @error('label') is-invalid @enderror">
                    <option value="">Selecione...</option>
                    @foreach(\App\Models\TaskLabel::cases() as $label)
                        <option value="{{ $label->value }}" {{ old('label') == $label->value ? 'selected' : '' }}>
                            {{ $label->label() }}
                        </option>
                    @endforeach
                </select>
                @error('label')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Datas --}}
    <div class="row">
        <div class="col-md-6">
            <x-form.input type="date" name="start_date" label="Data de Início" />
        </div>
        <div class="col-md-6">
            <x-form.input type="date" name="due_date" label="Data de Entrega (Prazo)" />
        </div>
    </div>

    {{-- Descrição --}}
    <div class="row">
        <div class="col-12">
            <x-form.textarea name="description" label="Descrição Detalhada" rows="4" />
        </div>
    </div>

    {{-- Ações --}}
    <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-outline-secondary mr-2" data-toggle="collapse" data-target="#collapseNovaTask">
            Cancelar
        </button>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Criar Tarefa
        </button>
    </div>
</form>