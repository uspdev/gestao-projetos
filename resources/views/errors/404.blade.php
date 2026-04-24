@extends('layouts.app')

@section('content')
  @if (Gate::check('user'))
    <div class="h4 text-danger">
      <i class="far fa-times-circle"></i> Página não encontrada!!
    </div>
    A página que você procura não existe!
  @else
    <div class="h4 text-primary">
      <a href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i></a>
    </div>
    Faça <a href="{{ route('login') }}">login</a> para acessar esta página.
  @endif
@endsection
