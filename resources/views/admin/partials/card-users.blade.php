<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">Usuários com projetos</h5>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-striped datatable-simples">
      <thead>
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
              <ul class="mb-0 ps-3">
                @foreach ($user->projects as $project)
                  <li><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
                @endforeach
              </ul>
            </td>
            <td>
              {{ $user->projects->count() }}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
