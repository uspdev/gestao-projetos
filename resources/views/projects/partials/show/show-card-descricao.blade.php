{{-- Card: Título e Descrição --}}
<div class="card mb-4">
  <div class="card-header h5 py-2" style="background-color: lightCyan;">
    Descrição
    @include('projects.partials.buttons.edit-btn')
  </div>

  <div class="card-body">
    {{-- Descrição --}}
    <div class="text-justify">
      @if ($project->description)
        <x-markdown-content :text="$project->description" />
      @else
        <div class="text-center text-muted p-5 bg-light rounded">
          <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
          <h5>Sem descrição</h5>
          <p class="mb-0">Nenhuma descrição foi fornecida para este projeto.</p>
        </div>
      @endif
    </div>

  </div>

</div>
