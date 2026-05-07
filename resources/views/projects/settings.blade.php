@extends('layouts.app')

@section('title', 'Configurações do Projeto')
@section('content')
  {{-- Card: Título e Descrição --}}
  <div class="card">
    @include('projects.partials.show-header')
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
            <td>@include('projects.partials.update-status')</td>
          </tr>
          <tr>
            <td>Membros</td>
            <td>@include('projects.partials.show-card-membros')</td>
          </tr>
          <tr>
            <td>Módulos</td>
            <td>
              Tarefas, Reuniões (a implementar)
            </td>
          </tr>
          <tr>
            <td>Remover projeto</td>
            <td> @include('projects.partials.delete-btn')</td>
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
