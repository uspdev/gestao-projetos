<div class="card border">
  <div class="card-header d-flex align-items-center py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-user-shield mr-1" aria-hidden="true"></i>
      {{ $project->isSubproject() ? 'Herança de permissões' : 'Herança nos subprojetos' }}
    </h6>
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

    <p class="small text-muted mb-3">
      Quando uma pessoa possui vínculo direto com o subprojeto, a função local prevalece.
    </p>

    @if ($project->isSubproject())
      <div class="d-flex align-items-center justify-content-between flex-wrap border-top pt-3">
        <span class="small font-weight-bold text-muted mr-3 mb-1">
          Configuração atual
        </span>
        <div class="mb-1">
          @include('projects.partials.components.update-permission-inheritance')
        </div>
      </div>
    @elseif ($project->isOrganizational())
      <a href="{{ route('projects.subprojects.members', $project) }}" class="btn btn-outline-primary btn-sm btn-block">
        <i class="fas fa-users-cog mr-1" aria-hidden="true"></i>
        Gerenciar membros dos subprojetos
      </a>
    @endif
  </div>
</div>
