{{-- Breadcrumb simplificado --}}
<div class="mb-4 h5">
  <a href="{{ route('users.projects.index', auth()->id()) }}" class="text-decoration-none text-secondary fw-medium">
    Meus Projetos
  </a>
  <i class="fas fa-angle-right text-muted"></i> {{ $project->name }}
  @include('projects.partials.edit-btn')

  <a href="#" class="btn btn-sm btn-outline-secondary ml-3">
    Tarefas
  </a>

</div>
