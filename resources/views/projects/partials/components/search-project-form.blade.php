<form method="GET" action="{{ route('projects.index') }}" class="d-flex align-items-center ml-4 mt-2" style="gap: .5rem;">
  <input id="project-search" name="search" type="search" class="form-control form-control-sm" placeholder="Buscar projeto"
    value="{{ $search }}" style="width: 250px;" autofocus>
  <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
  @if ($search !== '')
    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
  @endif
</form>
