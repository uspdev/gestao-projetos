@extends('layouts.app')

@section('title', 'Meus Projetos')

@section('content')
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Meus Projetos</h2>
      @include('projects.partials.create-btn')
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
