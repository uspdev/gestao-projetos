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
              <li><b>{{ $module->name }}</b>: {!! md2html($module->description) !!}</li>
            @endforeach
          </ul>
          <hr />
          <p class="card-text mb-0">Tags:</p>
          <ul class="mb-0">
            @foreach ($tags as $tag)
              <li><b>{{ $tag->name }}</b>: {{ $tag->type }}</li>
            @endforeach
          </ul>
        </div>
        <div class="col-md-4">
          <p class="card-text mb-0">Tipos de projeto:</p>
          <ul class="mb-0">
            @foreach ($projectTypes as $type)
              <li class="py-2">
                <b>{{ $type->name }}</b>:
                {!! md2html($type->description) !!}
                <div class="ml-2"><b>Módulos</b>: {{ $type->modules->pluck('name')->implode(', ') }}</div>
                @if ($type->modules->contains('slug', 'phases'))
                  <div class="ml-2"><b>Fases</b>: {{ $type->phases->pluck('name')->implode(', ') }}</div>
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>

  @include('admin.partials.card-users')

@endsection
