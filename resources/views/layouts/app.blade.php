@extends('laravel-usp-theme::master')

{{-- Blocos do laravel-usp-theme --}}
{{-- Ative ou desative cada bloco --}}

{{-- Target:card-header; class:card-header-sticky --}}
@include('laravel-usp-theme::blocos.sticky')

{{-- Target: button, a; class: btn-spinner, spinner --}}
@include('laravel-usp-theme::blocos.spinner')

{{-- Target: table; class: datatable-simples --}}
@include('laravel-usp-theme::blocos.datatable-simples')

{{-- Target: textarea; class: textarea-autogrow --}}
@include('blocos.textarea-autogrow')

{{-- Fim de blocos do laravel-usp-theme --}}

@section('styles')
  @parent
  <style>
    html {
      scroll-behavior: smooth;
    }

    /* anchor scroll por causa do header */
    [id] {
      scroll-margin-top: 70px;
    }

    /*seus estilos*/
    .gap-2>*+* {
      margin-left: 0.5rem;
    }

    /* Rodapé sempre em baixo */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    #skin_footer {
      /* flex-shrink -> ele não se redimensiona */
      flex-shrink: 0;
      margin-top: auto;
    }

    .badge-outline-primary {
      color: #007bff;
      border: 1px solid #007bff;
      background: transparent;
    }

    .badge-outline-success {
      color: #28a745;
      border: 1px solid #28a745;
      background: transparent;
    }

    .badge-outline-danger {
      color: #dc3545;
      border: 1px solid #dc3545;
      background: transparent;
    }

    .badge-outline-warning {
      color: #ffc107;
      border: 1px solid #ffc107;
      background: transparent;
    }

    .badge-outline-secondary {
    color: #6c757d;
    background-color: transparent;
    border: 1px solid #6c757d;
}
  </style>
@endsection

@section('javascripts_bottom')
  @stack('modals')
  @parent
  @stack('scripts')
  <script>
    // Seu código .js
  </script>
@endsection
