@extends('layouts.app')

@php
  $parentProject = $parentProject ?? null;
  $creationTitle = $parentProject ? 'Novo subprojeto' : 'Novo Projeto';
  $typeSelectionParams = $parentProject ? ['parent_id' => $parentProject->id] : [];
@endphp

@section('title', $title . ' | ' . $creationTitle)

@section('content')
  <div class="container-fluid">
    <div class="mb-4">
      <h2 class="mb-1">{{ $creationTitle }}</h2>
      @if ($parentProject)
        <p class="text-muted mb-0">
          Escolha o tipo do subprojeto que será criado em
          <a href="{{ route('projects.show', [$parentProject, 'view' => 'subprojects']) }}">
            {{ $parentProject->name }}
          </a>.
        </p>
      @else
        <p class="text-muted mb-0">Escolha o tipo de projeto que deseja criar.</p>
      @endif
    </div>

    @unless ($parentProject)
      <div class="row">
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title mb-0">{{ $organizacional->name }}</h5>
              </div>
              <div class="text-muted"><x-markdown.markdown-content :text="$organizacional->description" /></div>
              <div class="mb-3">
                <strong class="d-block mb-2">Módulos ativos</strong>
                <ul class="mb-0">
                  @foreach ($organizacional->enabledModules() as $module)
                    <li>{{ $module->name }}</li>
                  @endforeach
                </ul>
              </div>
              <a class="btn btn-success mt-auto"
                href="{{ route('projects.create', ['project_type' => $organizacional->slug]) }}">
                Criar
              </a>
            </div>
          </div>
        </div>
      </div>
    @endunless

    <div class="row">
      @forelse ($projectTypes as $projectType)
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title mb-0">{{ $projectType->name }}</h5>
              </div>
              <div class="text-muted"><x-markdown.markdown-content :text="$projectType->description" /></div>
              <div class="mb-3">
                <strong class="d-block mb-2">Módulos ativos</strong>
                @if ($projectType->enabledModules()->isNotEmpty())
                  <ul class="mb-0">
                    @foreach ($projectType->enabledModules() as $module)
                      <li>{{ $module->name }}</li>
                    @endforeach
                  </ul>
                @else
                  <div class="text-muted">Nenhum módulo ativo.</div>
                @endif
              </div>

              <a class="btn btn-primary mt-auto"
                href="{{ route('projects.create', array_merge(['project_type' => $projectType->slug], $typeSelectionParams)) }}">
                Criar
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
