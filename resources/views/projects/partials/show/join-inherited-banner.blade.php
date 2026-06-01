@php
  $user = auth()->user();
  $inheritedRole = $user ? $user->getInheritedRoleFor($project) : null;
@endphp

@if ($inheritedRole)
  <div class="alert alert-warning shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div class="mb-3 mb-md-0">
      <i class="fas fa-info-circle mr-2"></i>
      Você possui permissões de <strong>{{ $inheritedRole->label() }}</strong> herdadas do projeto pai. 
      <br class="d-none d-md-block">
      Para interagir de forma completa (criar tarefas, reuniões, gerenciar configurações), você precisa ingressar ativamente neste subprojeto.
    </div>
    <form action="{{ route('projects.members.joinInherited', $project) }}" method="POST" class="m-0">
      @csrf
      <button type="submit" class="btn btn-warning text-nowrap">
        <i class="fas fa-sign-in-alt mr-1"></i> Participar do Projeto
      </button>
    </form>
  </div>
@endif