{{-- Breadcrumb simplificado --}}
<div class="mb-4 h5">
  <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary fw-medium">
    Meus Projetos
  </a>
  <x-separator /> {{ $project->name }}
  @include('projects.partials.edit-btn')

  <a href="#" class="btn btn-sm btn-outline-secondary ml-3">
    Tarefas
  </a>

</div>
