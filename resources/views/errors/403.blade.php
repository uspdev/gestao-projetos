@extends('layouts.app')

@section('content')
  @if (Gate::check('user'))
    <h4 class="text-warning">
      <i class="fas fa-exclamation-triangle"></i> Acesso negado!!
    </h4>
    Você não tem privilégios para acessar esse recurso.
  @else
    <div class="h4 text-primary">
      <a href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i></a>
    </div>
    Faça <a href="{{ route('login') }}">login</a> para acessar esta página.
  @endif
@endsection
