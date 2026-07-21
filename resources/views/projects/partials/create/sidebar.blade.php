<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title">{{ $projectType->name }}</h5>
    @if ($projectType->description)
      <div class="text-muted"><x-markdown-content :text="$projectType->description" /></div>
    @else
      <p class="text-muted">Sem descrição cadastrada para este tipo de projeto.</p>
    @endif
    <div>
      <strong class="d-block mb-2">Módulos ativos</strong>
      @if ($activeModules->isNotEmpty())
        <ul class="mb-0">
          @foreach ($activeModules as $module)
            <li>{{ $module->name }}</li>
          @endforeach
        </ul>
      @else
        <div class="text-muted">Nenhum módulo ativo.</div>
      @endif
    </div>
  </div>
</div>
