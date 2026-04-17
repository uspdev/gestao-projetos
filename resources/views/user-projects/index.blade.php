@extends('layouts.app')

@section('title', 'Meus Projetos')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Meus Projetos</h2>
            {{-- Botão que exibe o Modal de criação --}}
            @can('create', \App\Models\Project::class)
                <button class="btn btn-success" type="button" data-toggle="modal" data-target="#modalNovoProjeto">
                    <i class="fas fa-plus"></i> Novo Projeto
                </button>
            @endcan
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

    {{-- Inclusão do Modal de Criação de Project --}}
    @include('projects.create')

    @if ($errors->any() && old('name') !== null)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $('#modalNovoProjeto').modal('show');
            });
        </script>
    @endif

@endsection