@extends('layouts.app')

@section('title', 'Configurações do Projeto')
@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-lg-8 mb-4">
          <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">Configurações principais</h5>
                <small class="text-muted">Dados básicos e controles de acesso do projeto.</small>
              </div>
            </div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0 align-middle">
                <tbody>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small" style="width: 34%;">Nome do Projeto</td>
                    <td>@include('projects.partials.components.update-name')</td>
                  </tr>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small">URL do Projeto (Slug)</td>
                    <td>@include('projects.partials.components.update-slug')</td>
                  </tr>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small">Status</td>
                    <td>@include('projects.partials.components.update-status')</td>
                  </tr>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small">Fase</td>
                    <td>@include('projects.partials.components.update-phase')</td>
                  </tr>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small">Visibilidade</td>
                    <td>@include('projects.partials.components.update-visibility')</td>
                  </tr>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small">Herança de permissões</td>
                    <td>@include('projects.partials.components.update-permission-inheritance')</td>
                  </tr>
                  <tr>
                    <td class="text-muted font-weight-bold text-uppercase small">Tags</td>
                    <td>@include('projects.partials.components.update-tags')</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="mb-4">
            @include('projects.partials.show.show-card-membros')
          </div>

          <div class="mb-4">
            @include('projects.partials.show.show-card-modulos')
          </div>

          <div class="card border-danger">
            <div class="card-header bg-light text-danger font-weight-bold">
              Área de risco
            </div>
            <div class="card-body">
              <p class="text-muted mb-3">
                A remoção do projeto é permanente e deve ser usada com cuidado.
              </p>
              @include('projects.partials.buttons.delete-btn')
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
