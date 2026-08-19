<div class="card config-card mb-4">
  <div class="card-header d-flex align-items-center py-2">
    <h6 class="m-0 text-muted">
      <i class="fas fa-bell mr-1" aria-hidden="true"></i> Notificações
    </h6>
  </div>
  <div class="card-body">
    <p class="mb-2">
      Ative esta opção para receber por e-mail um resumo das atividades relevantes deste projeto,
      como novos comentários e vínculos ou desvínculos de subprojetos.
    </p>
    <p class="text-muted small mb-3">
      A configuração vale somente para este projeto. Tarefas, reuniões e subprojetos possuem
      preferências independentes.
    </p>

    @include('watches.partials.control', ['watchable' => $project, 'showLabel' => true])
  </div>
</div>
