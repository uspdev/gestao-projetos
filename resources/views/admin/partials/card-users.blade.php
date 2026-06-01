<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Usuários com projetos</h5>
    <span class="badge badge-info badge-pill">Relacionamentos</span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover datatable-simples mb-0">
        <thead class="thead-light">
          <tr>
            <th>Usuário</th>
            <th>Email</th>
            <th>Projetos</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($usersWithProjects as $user)
            <tr>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>
                <ul class="mb-0 pl-3">
                  @foreach ($user->projects as $project)
                    <li><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
                  @endforeach
                </ul>
              </td>
              <td>
                <span class="badge badge-primary">{{ $user->projects->count() }}</span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
