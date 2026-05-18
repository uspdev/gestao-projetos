@extends('layouts.app')

@section('title', 'Área Administrativa')

@section('content')

  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0">Geral</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4" style="border-right: 2px solid gray;">
          <p class="card-text mb-0">Estatísticas:</p>
          <ul class="mb-0">
            <li>Total de Projetos: {{ $stats['projects'] }}</li>
            <li>Total de Tarefas: {{ $stats['tasks'] }}</li>
            <li>Total de Usuários: {{ $stats['users'] }}</li>
            <li>Total de Reuniões: {{ $stats['meetings'] }}</li>
          </ul>
        </div>
        <div class="col-md-4" style="border-right: 2px solid gray;">
          <p class="card-text mb-0">Módulos:</p>
          <ul class="mb-0">
            @foreach ($modules as $module)
              <li>{{ $module->name }}</li>
            @endforeach
          </ul>
        </div>
        <div class="col-md-4">
          <p class="card-text mb-0">Tipos de projeto:</p>
          <ul class="mb-0">
            @foreach ($projectTypes as $type)
              <li>{{ $type->name }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>

  @include('admin.partials.card-users')

@endsection
