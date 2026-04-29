<div class="card-body">
  <div class="row">
    <div class="col-md-8">
      @forelse($tasks as $task)
        @include('partials.tasks.preview')
      @empty
        <div class="col-12">
          <div class="alert alert-secondary text-center p-4 shadow-sm" role="alert">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h5 class="text-muted m-0">Nenhuma tarefa encontrada.</h5>
            <p class="text-muted mb-0 mt-2">
              Clique em <strong>"Nova Tarefa"</strong> acima para criar a primeira tarefa deste projeto.
            </p>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</div>
