@extends('layouts.app')

@push('styles')
  <style>
    .card {
      transition: all .2s ease;
      cursor: pointer;
    }

    .card:not(.disabled):hover {
      transform: translateY(-4px);
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
      border: 1px solid #007bff !important;
    }

    .card.disabled {
      opacity: .65;
      cursor: not-allowed;
    }
  </style>
@endpush
@section('content')
  <div class="h4">Novo projeto</div>
  <div class="mb-4 text-muted">Escolha o tipo de projeto que deseja criar:</div>

  <div class="row">
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="card-title mb-0">Organização / Programa / Container <span class="badge badge-success">Comece aqui</span></h5>
          </div>
          <p class="text-muted">
            Estrutura organizacional para agrupar múltiplos projetos relacionados, permitindo gestão centralizada e visão
            consolidada.<br>
            Dentro do <b>container</b> pode-se criar quaisquer outros tipos de projetos.
          </p>
          <div class="mb-3">
            <strong class="d-block mb-2">Módulos ativos</strong>
            <ul class="mb-0">
              <li>Status gerencial</li>
              <li>Reuniões</li>
            </ul>
          </div>
          <button class="btn btn-primary mt-auto">Criar Programa</button>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="card-title mb-0">Software</h5>
          </div>
          <p class="text-muted">
            Projeto operacional com fluxo de desenvolvimento, acompanhamento, gerenciamento de entregas, produção e atualizações.
          </p>
          <div class="mb-3">
            <strong class="d-block mb-2">Módulos ativos</strong>
            <ul class="mb-0">
              <li>Fases do projeto</li>
              <li>Tarefas</li>
              <li>Versionamento <span class="badge badge-warning">futuro</span></li>
            </ul>
          </div>
          <button class="btn btn-primary mt-auto">Criar Software</button>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card h-100 disabled">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="card-title mb-0">Acadêmico <span class="badge badge-warning">futuro</span></h5>
          </div>
          <p class="text-muted">
            Projeto acadêmico destinado ao acompanhamento de atividades de pesquisa,
            orientação e produção científica, permitindo controle de etapas,
            entregas, revisões e colaboração entre orientador e aluno.
          </p>
          <div class="mb-3">
            <strong class="d-block mb-2">Módulos ativos</strong>
            <ul class="mb-0">
              <li>Fases do projeto</li>
              <li>Tarefas</li>
              <li>Arquivos</li>
            </ul>
          </div>
          <button class="btn btn-primary mt-auto" disabled>Criar Acadêmico</button>
        </div>
      </div>
    </div>

  </div>
@endsection
