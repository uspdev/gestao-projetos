<form action="{{ route('projects.store') }}" method="POST">
    @csrf
    <x-form.input name="name" label="Nome do Projeto" required />
    
    <div class="form-group mb-3">
        <label for="status">Status Inicial</label>
        <select name="status" id="status" class="form-control">
            <option value="DEVELOPMENT">Em Desenvolvimento</option>
            <option value="PRODUCTION">Produção</option>
        </select>
    </div>

    <x-form.textarea name="description" label="Descrição do Projeto" rows="3" />
    
    <button type="submit" class="btn btn-primary">Salvar Projeto</button>
</form>