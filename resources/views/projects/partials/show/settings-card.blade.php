<style>
  .settings-card {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
  }

  .settings-card .settings-table {
    margin: 0;
    font-size: 14px;
  }

  .settings-section-header {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #9ca3af;
    padding: 0.55rem 1.25rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
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
    padding: 0.8rem 1.25rem;
    vertical-align: middle;
    border: none;
    background: transparent;
  }

  .settings-table>tbody>tr>td:first-child {
    width: 220px;
  }

  .settings-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    white-space: nowrap;
  }

  .settings-label i {
    font-size: 15px;
    color: #9ca3af;
    flex-shrink: 0;
  }

  .settings-field-group {
    width: 100%;
    max-width: 520px;
  }

  .settings-field-group .input-group {
    width: 100%;
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

  @media (max-width: 767.98px) {
    .settings-section-header {
      padding: 0.7rem 1rem;
    }

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

    .settings-table>tbody>tr>td:last-child {
      padding-left: 0;
    }

    .settings-label {
      white-space: normal;
      align-items: flex-start;
    }

    .settings-field-group {
      max-width: none;
    }

    .settings-tags-group {
      flex-direction: column;
      align-items: stretch;
    }

    .settings-tags-select {
      max-width: none;
    }

    .settings-tags-group .btn {
      width: 100%;
    }
  }
</style>

<div class="settings-card mb-3">
  {{-- Seção: Informações gerais --}}
  <div class="settings-section-header">Informações gerais</div>
  <table class="table settings-table">
    <tbody>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-folder" aria-hidden="true"></i>
            Nome do projeto
          </div>
        </td>
        <td>
          <div class="settings-field-group">@include('projects.partials.components.update-name')</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-link" aria-hidden="true"></i>
            URL do projeto (slug)
          </div>
        </td>
        <td>
          <div class="settings-field-group">@include('projects.partials.components.update-slug')</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-circle-check" aria-hidden="true"></i>
            Status
          </div>
        </td>
        <td>@include('projects.partials.components.update-status')</td>
      </tr>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-git-branch" aria-hidden="true"></i>
            Fase
          </div>
        </td>
        <td>@include('projects.partials.components.update-phase')</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="settings-card mb-3">
  {{-- Seção: Acesso e permissões --}}
  <div class="settings-section-header">Acesso e permissões</div>
  <table class="table settings-table">
    <tbody>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-eye" aria-hidden="true"></i>
            Visibilidade
          </div>
        </td>
        <td>@include('projects.partials.components.update-visibility')</td>
      </tr>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-shield-check" aria-hidden="true"></i>
            Herança de permissões
          </div>
        </td>
        <td>@include('projects.partials.components.update-permission-inheritance')</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="settings-card mb-3">
  {{-- Seção: Classificação --}}
  <div class="settings-section-header">Classificação</div>
  <table class="table settings-table">
    <tbody>
      <tr>
        <td>
          <div class="settings-label">
            <i class="ti ti-tag" aria-hidden="true"></i>
            Tags
          </div>
        </td>
        <td>
          <div class="settings-field-group">@include('projects.partials.components.update-tags')</div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
