@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  <div class="container-fluid">
    {{-- Card de Informações do Usuário --}}
    @include('users.partials.user-info')

    @if (Auth::id() === $user->id)
      <hr class="my-4">
      @include('projects.partials.user-dashboard')

      <hr class="my-4">
      @include('module-tasks.partials.user-dashboard')
    @endif

  </div>

@endsection
