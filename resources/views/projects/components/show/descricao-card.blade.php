@props(['project', 'type' => 'normal'])

<x-projects::show.card-template :type="$type">
  <x-slot:header>
    <i class="fas fa-id-card mr-2"></i>
    Descrição
    @include('projects.partials.buttons.edit-btn')
  </x-slot:header>

  <div class="text-justify">
    @if ($project->description)
      <x-markdown-content :text="$project->description" />
    @else
      <div class="text-center text-muted p-5 bg-light rounded">
        <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
        <h5>Sem descrição</h5>
        <p class="mb-0">
          Nenhuma descrição foi fornecida para este projeto.
        </p>
      </div>
    @endif
  </div>
</x-projects::show.card-template>
