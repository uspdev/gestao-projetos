@extends('emails.layouts.base')

@section('content')
  <p>O projeto "{{ $subproject->name }}" foi desvinculado do projeto organizacional.</p>
  <p>Projeto organizacional: "{{ $parentProject->name }}".</p>
  <p>Responsável pela desvinculação: {{ $actor->name }}.</p>
@endsection
