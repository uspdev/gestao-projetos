@extends('layouts.app')

@section('title', 'Novo Projeto')

@section('content')
  <div class="container-fluid">
    <div class="mb-4">
      <h2 class="mb-1">Novo projeto</h2>
      <p class="text-muted mb-0">Escolha o tipo de projeto que deseja criar.</p>
    </div>

    <div class="row">
      @forelse ($projectTypes as $projectType)
        @php
          $activeModules = $projectType->modules
              ->filter(fn($module) => (bool) ($module->pivot?->enabled ?? false))
              ->values();
        @endphp
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title mb-0">{{ $projectType->name }}</h5>
              </div>

              @if ($projectType->description)
                <div class="text-muted">{!! md2html($projectType->description) !!}</div>
              @else
                <p class="text-muted">Sem descrição cadastrada para este tipo de projeto.</p>
              @endif

              <div class="mb-3">
                <strong class="d-block mb-2">Módulos ativos</strong>
                @if ($activeModules->isNotEmpty())
                  <ul class="mb-0">
                    @foreach ($activeModules as $module)
                      <li>{{ $module->name }}</li>
                    @endforeach
                  </ul>
                @else
                  <div class="text-muted">Nenhum módulo ativo.</div>
                @endif
              </div>

              <a class="btn btn-primary mt-auto"
                href="{{ route('projects.create', ['project_type' => $projectType->slug]) }}">
                Escolher tipo
              </a>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-warning mb-0">
            Nenhum tipo de projeto cadastrado.
          </div>
        </div>
      @endforelse
    </div>
  </div>
@endsection
