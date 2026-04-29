@extends('layouts.app')

@section('title', 'Meus Projetos')

@section('content')
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Meus Projetos</h2>
      @includeWhen(auth()->user()->can('create', \App\Models\Project::class), 'projects.create')
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
  {{-- Reabre o modal caso haja erro de validação na edição (Vanilla JS) --}}
  @if ($errors->any() && old('name') !== null)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        $('#modalNovoProjeto').modal('show');
      });
    </script>
  @endif

@endsection
