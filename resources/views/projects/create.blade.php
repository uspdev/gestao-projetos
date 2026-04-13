<div class="modal fade" id="modalNovoProjeto" tabindex="-1" aria-labelledby="modalNovoProjetoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovoProjetoLabel">Novo Projeto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('projects.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <x-form.input name="name" label="Nome do Projeto" required />

                    <div class="form-group mb-3">
                        <label for="status">Status Inicial <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="">Selecione o status...</option>
                            @foreach (\App\Enums\Project\ProjectStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('status') === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <x-form.textarea name="description" label="Descrição do Projeto" rows="3" />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Projeto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>