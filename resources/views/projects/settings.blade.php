@extends('layouts.app')

@section('title', 'Configurações do Projeto')
@section('content')
  {{-- Card: Título e Descrição --}}
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <table class="table">
        <tbody>
          <tr>
            <td>
              <strong>Nome do Projeto</strong>
            </td>
            <td>
              <input type="text" name="name" class="form-control" value="{{ $project->name }}">
            </td>
          </tr>
          <tr>
            <td>Status</td>
            <td>@include('projects.partials.components.update-status')</td>
          </tr>
          <tr>
            <td>Fase</td>
            <td>@include('projects.partials.components.update-phase')</td>
          </tr>
          <tr>
            <td>Visibilidade</td>
            <td>@include('projects.partials.components.update-visibility')</td>
          </tr>
          <tr>
            <td>Heranca de permissoes</td>
            <td>@include('projects.partials.components.update-permission-inheritance')</td>
          </tr>
          <tr>
            <td>Tipo de projeto</td>
            <td>
              @if ($project->projectType)
                <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1 mb-1">
                  <span>
                    {{ $project->projectType->name }}
                    <small class="text-muted">({{ $project->projectType->slug }})</small>
                  </span>
                  <span class="badge badge-info">Definido</span>
                </div>
              @else
                <span class="text-muted">Sem tipo definido.</span>
              @endif
            </td>
          </tr>
          <tr>
            <td>Membros</td>
            <td>@include('projects.partials.show.show-card-membros')</td>
          </tr>
          <tr>
            <td>Módulos</td>
            <td>
              @forelse (($resolvedModules ?? []) as $module)
                <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1 mb-1">
                  <span>
                    {{ $module['name'] }}
                    <small class="text-muted">({{ $module['slug'] }})</small>
                  </span>
                  <span class="badge {{ $module['enabled'] ? 'badge-success' : 'badge-secondary' }}">
                    {{ $module['enabled'] ? 'Ativo' : 'Inativo' }}
                  </span>
                </div>
              @empty
                <span class="text-muted">Nenhum módulo configurado.</span>
              @endforelse
            </td>
          </tr>
          <tr>
            <td>Remover projeto</td>
            <td> @include('projects.partials.buttons.delete-btn')</td>
          </tr>
        </tbody>
      </table>

      <div class="row mb-4">
      </div>
      <div class="row">
      </div>
    </div>
  </div>
@endsection
