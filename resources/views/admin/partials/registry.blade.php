<div class="row mb-4">
  <div class="col-lg-6 mb-3 mb-lg-0">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white d-flex align-items-center">
        <h5 class="mb-0">Módulos e tags</h5>
        <span class="badge badge-info badge-pill ml-2">Catálogo</span>
      </div>
      <div class="card-body">
        <div class="mb-4">
          <h6 class="text-muted text-uppercase small mb-3">Módulos</h6>
          <ul class="list-unstyled mb-0">
            @foreach ($modules as $module)
              <li class="mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <strong>{{ $module->name }}</strong>
                </div>
                <div class="text-muted"><x-markdown.markdown-content :text="$module->description" /></div>
              </li>
            @endforeach
          </ul>
        </div>

        <div>
          <h6 class="text-muted text-uppercase small mb-3">Tags</h6>
          <ul class="list-unstyled mb-0">
            @foreach ($tags as $tag)
              <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><strong>{{ $tag->name }}</strong></span>
                <span class="badge badge-secondary">{{ $tag->type }}</span>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white d-flex align-items-center">
        <h5 class="mb-0">Tipos de projeto</h5>
        <span class="badge badge-info badge-pill ml-2">Configuração</span>
      </div>
      <div class="card-body">
        <div class="list-group list-group-flush">
          @foreach ($projectTypes as $type)
            <div class="list-group-item px-0 py-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h6 class="mb-1">{{ $type->name }}</h6>
                  <div class="text-muted"><x-markdown.markdown-content :text="$type->description" /></div>
                </div>
              </div>

              <div class="mt-3">
                <div class="small text-muted mb-1">Módulos</div>
                <div class="d-flex flex-wrap">
                  @foreach ($type->modules as $module)
                    <span class="badge badge-info mr-2 mb-2">{{ $module->name }}</span>
                  @endforeach
                </div>
              </div>

              @if ($type->modules->contains('slug', 'phases'))
                <div class="mt-3">
                  <div class="small text-muted mb-1">Fases</div>
                  <div class="d-flex flex-wrap">
                    @foreach ($type->phases as $phase)
                      <span class="badge badge-light border mr-2 mb-2">{{ $phase->name }}</span>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
