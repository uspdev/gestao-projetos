<div class="row mb-4">
  <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="small text-uppercase text-muted font-weight-bold mb-2">Projetos</div>
        <div class="h2 mb-2">{{ $stats['projects'] }}</div>
        <div class="text-muted small">Total de projetos cadastrados.</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="small text-uppercase text-muted font-weight-bold mb-2">Tarefas</div>
        <div class="h2 mb-2">{{ $stats['tasks'] }}</div>
        <div class="text-muted small">Itens em acompanhamento.</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="small text-uppercase text-muted font-weight-bold mb-2">Usuários</div>
        <div class="h2 mb-2">{{ $stats['users'] }}</div>
        <div class="text-muted small">Contas ativas no sistema.</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="small text-uppercase text-muted font-weight-bold mb-2">Reuniões</div>
        <div class="h2 mb-2">{{ $stats['meetings'] }}</div>
        <div class="text-muted small">Eventos e encontros registrados.</div>
      </div>
    </div>
  </div>
</div>
