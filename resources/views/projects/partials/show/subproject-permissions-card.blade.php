<div class="card border">
  <div class="card-header h6 py-2">
    <i class="fas fa-user-shield mr-1" aria-hidden="true"></i>
    {{ $project->isSubproject() ? 'Herança de permissões' : 'Herança nos subprojetos' }}
  </div>

  <div class="card-body">
    <p class="mb-2">
      @if ($project->isSubproject())
        Os membros do projeto organizacional pai podem visualizar este subprojeto conforme a herança configurada.
      @else
        Os membros do projeto organizacional podem visualizar cada subprojeto conforme a herança configurada nele.
      @endif
      A herança não os adiciona automaticamente à equipe do subprojeto.
    </p>

    <ul class="small text-muted pl-4 mb-3">
      <li>
        <strong>Sem Herança:</strong> o vínculo com o projeto organizacional não concede acesso ao subprojeto.
      </li>
      <li><strong>Apenas Leitura:</strong> os membros do projeto organizacional também podem visualizar o subprojeto.</li>
      <li>
        <strong>Herança Total:</strong> admins e colaboradores podem ingressar no subprojeto com a mesma função.
      </li>
    </ul>

    <p class="small text-muted">
      Quando uma pessoa possui vínculo direto com o subprojeto, a função local prevalece.
    </p>

    @if ($project->isOrganizational())
      <a href="{{ route('projects.subprojects.members', $project) }}" class="btn btn-outline-primary btn-sm btn-block">
        <i class="fas fa-users-cog mr-1" aria-hidden="true"></i>
        Gerenciar membros dos subprojetos
      </a>
    @endif
  </div>
</div>
