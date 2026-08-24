@extends('layouts.app')

@section('title', $title . ' | Meus Projetos')

@section('content')

  <div @if(isset($user)) id="{{ deep_link_fragment($user) }}" tabindex="-1" data-deep-link-target @endif
    class="py-1 px-5">
    <i class="fa fa-folder-open fa-4x text-muted mb-3"></i>

    <h4 class="mb-3">Seu espaço de projetos está vazio</h4>

    <p class="text-muted mb-4">
      Comece criando seu primeiro projeto para organizar ideias,
      acompanhar atividades e centralizar informações da equipe.
    </p>

    <a href="{{ route('projects.create') }}" class="btn btn-success">
      <i class="fa fa-plus"></i> Criar Primeiro Projeto
    </a>
  </div>

@endsection
