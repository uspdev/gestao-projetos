@extends('layouts.app')

@section('title', $title . ' | Chaves de API')

@section('content')
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-2 card-header-sticky">
      <div>
        <h1 class="h4 mb-1">{{ $clientSystem->name }}</h1>
        <p class="text-muted mb-0">Chaves de API do Sistema cliente no Projeto {{ $project->name }}.</p>
      </div>
      <a class="btn btn-sm btn-outline-secondary"
        href="{{ route('projects.settings', $project) }}#project-integrations-settings">
        Voltar às configurações
      </a>
    </div>
    <div class="card-body">
      <x-api-keys::manager :owner="$clientSystem" owner-alias="client-system" />
    </div>
  </div>
@endsection
