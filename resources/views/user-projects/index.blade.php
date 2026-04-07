@extends('layouts.app')

@section('title', 'Meus Projetos')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Meus Projetos</h2>
        {{-- Botão que exibe o formulário de criação --}}
        <button class="btn btn-success" type="button" data-toggle="collapse" data-target="#collapseNovoProjeto" aria-expanded="false" aria-controls="collapseNovoProjeto">
            <i class="fas fa-plus"></i> Novo Projeto
        </button>
    </div>

    {{-- Form de Criação de Project --}}
    <div class="collapse mb-4" id="collapseNovoProjeto">
        <div class="card card-body bg-light">
            @include('projects.create')
        </div>
    </div>

    {{-- Listagem de Previews --}}
    <div class="row">
        @forelse($projects as $project)
            <div class="col-md-4">
                @include('partials.projects.preview', ['project' => $project])
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Você ainda não possui projetos em andamento.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection