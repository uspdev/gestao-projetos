<div class="card mb-4">
  <div class="card-header h5">
    Detalhes
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <tbody>
        <tr>
          <td class="text-muted">Tipo de projeto</td>
          <td>{{ $project->projectType?->name ?? 'Nao definido' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Visibilidade</td>
          <td>{{ $project->visibility?->label() ?? 'Nao definido' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Heranca de permissoes</td>
          <td>{{ $project->permission_inheritance?->label() ?? 'Nao definido' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Fase</td>
          <td>{{ $project->phase?->label() ?? 'Nao definido' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
