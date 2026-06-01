@extends('emails.layouts.base')

@section('content')
  <p>O projeto "{{ $subproject->name }}" foi vinculado como subprojeto.</p>
  <p>Projeto pai: "{{ $parentProject->name }}".</p>
  <p>Responsavel pela vinculacao: {{ $actor->name }}.</p>
@endsection
