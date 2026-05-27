@extends('layouts.app')

@section('title', 'Área Administrativa')

@section('content')
  <div class="py-3">

    @include('admin.partials.metrics')
    @include('admin.partials.registry')
    @include('admin.partials.card-users')
  </div>
@endsection
