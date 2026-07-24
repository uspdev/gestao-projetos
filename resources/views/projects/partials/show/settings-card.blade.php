<style>
  .settings-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    white-space: nowrap;
  }

  .settings-label i {
    color: #9ca3af;
  }

  .settings-tags-group {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .settings-tags-select {
    width: 100%;
    max-width: 420px;
  }

  .settings-table>tbody>tr {
    border-bottom: 1px solid #f1f3f5;
  }

  .settings-table>tbody>tr:last-child {
    border-bottom: none;
  }

  .settings-table>tbody>tr:hover {
    background-color: #f8f9fa;
  }

  .settings-table>tbody>tr>td {
    border: none !important;
    background: transparent;
    vertical-align: middle;
  }

  .settings-table>tbody>tr>td:first-child {
    width: 220px;
  }

  @media (max-width: 767.98px) {

    .settings-table,
    .settings-table>tbody,
    .settings-table>tbody>tr,
    .settings-table>tbody>tr>td {
      display: block;
      width: 100%;
    }

    .settings-table>tbody>tr {
      padding: 0.9rem 1rem;
    }

    .settings-table>tbody>tr>td {
      padding: 0;
    }

    .settings-table>tbody>tr>td:first-child {
      width: 100%;
      margin-bottom: 0.45rem;
    }

    .settings-label {
      white-space: normal;
    }
  }
</style>

<div class="card mb-4">
  <div class="card-header d-flex align-items-center py-2">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Informações gerais
      </h6>
    </div>
  </div>
  <table class="table mb-0 settings-table">
    <tbody>
      <tr>
        <td class="pl-3 pr-2 py-2">
          <div class="settings-label d-flex align-items-center gap-2">
            <i class="ti ti-folder" aria-hidden="true"></i>
            Nome do projeto
          </div>
        </td>
        <td class="pr-3 py-2">
          <div class="w-100" style="max-width: 520px;">
            @include('projects.partials.components.update-name')
          </div>
        </td>
      </tr>
      <tr>
        <td class="pl-3 pr-2 py-2">
          <div class="settings-label d-flex align-items-center gap-2">
            <i class="ti ti-link" aria-hidden="true"></i>
            URL do projeto (slug)
          </div>
        </td>
        <td class="pr-3 py-2">
          <div class="w-100" style="max-width: 520px;">
            @include('projects.partials.components.update-slug')
          </div>
        </td>
      </tr>
      <tr>
        <td class="pl-3 pr-2 py-2">
          <div class="settings-label d-flex align-items-center gap-2">
            <i class="ti ti-circle-check" aria-hidden="true"></i>
            Status
          </div>
        </td>
        <td class="pr-3 py-2">@include('projects.partials.components.update-status')</td>
      </tr>
      @if ($project->isModuleEnabled('phases'))
        <tr>
          <td class="pl-3 pr-2 py-2">
            <div class="settings-label d-flex align-items-center gap-2">
              <i class="ti ti-git-branch" aria-hidden="true"></i>
              Fase
            </div>
          </td>
          <td class="pr-3 py-2">
            <div class="w-100" style="max-width: 520px;">
              @include('module-phases.partials.update-phase')
            </div>
          </td>
        </tr>
      @endif
      <tr>
        <td class="pl-3 pr-2 py-2">
          <div class="settings-label d-flex align-items-center gap-2">
            <i class="ti ti-circle-check" aria-hidden="true"></i>
            Duplicar projeto
          </div>
        </td>
        <td class="pr-3 py-2">@include('projects.partials.show.duplicate-btn')</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="card mb-4">
  <div class="card-header d-flex align-items-center py-2">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-user-shield mr-1" aria-hidden="true"></i> Acesso e permissões
      </h6>
    </div>
  </div>
  <table class="table mb-0 settings-table">
    <tbody>
      <tr>
        <td class="pl-3 pr-2 py-2">
          <div class="settings-label d-flex align-items-center gap-2">
            <i class="ti ti-eye" aria-hidden="true"></i>
            Visibilidade
          </div>
        </td>
        <td class="pl-5 py-2">@include('projects.partials.components.update-visibility')</td>
      </tr>
      @if ($project->isSubproject())
        @if ($project->parent)
          <tr>
            <td class="pl-3 pr-2 py-2">
              <div class="settings-label d-flex align-items-center gap-2">
                <i class="ti ti-unlink" aria-hidden="true"></i>
                Desvincular do projeto organizacional
              </div>
            </td>
            <td class="pl-5 py-2">
              @include('projects.partials.buttons.unlink-subproject-btn', [
                  'project' => $project->parent,
                  'subproject' => $project,
              ])
            </td>
          </tr>
        @endif
      @elseif ($project->isOrganizational())
        <tr>
          <td class="pl-3 pr-2 py-2">
            <div class="settings-label d-flex align-items-center gap-2">
              <i class="ti ti-link" aria-hidden="true"></i>
              Vincular subprojeto
            </div>
          </td>
          <td class="pl-5 py-2">@include('projects.partials.buttons.link-subproject-btn')</td>
        </tr>
      @else
        <tr>
          <td class="pl-3 pr-2 py-2">
            <div class="settings-label d-flex align-items-center gap-2">
              <i class="ti ti-unlink" aria-hidden="true"></i>
              Vincular a um projeto organizacional
            </div>
          </td>
          <td class="pl-5 py-2">@include('projects.partials.buttons.link-parent-btn')</td>
        </tr>
      @endif
    </tbody>
  </table>
</div>
@if ($project->isOrganizational() || $project->isSubproject())
  <div class="mb-4">
    @include('projects.partials.show.subproject-permissions-card')
  </div>
@endif

<div class="card mb-4">
  <div class="card-header d-flex align-items-center py-2">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-tags mr-1" aria-hidden="true"></i> Classificação
      </h6>
    </div>
  </div>
  <table class="table mb-0 settings-table">
    <tbody>
      <tr>
        <td class="pl-3 pr-2 py-1">
          <div class="settings-label d-flex align-items-center gap-2">
            <i class="ti ti-tag" aria-hidden="true"></i>
            Tags
          </div>
        </td>
        <td class="pr-3 py-1">
          @include('projects.partials.components.update-tags')
        </td>
      </tr>
    </tbody>
  </table>
</div>
