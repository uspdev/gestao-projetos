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
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.css"
    integrity="sha384-3AvV7152TgYAMYdGZPqG9BpmSH2ZW6ewTDL0QV5PyNkl19KMI+yLMdJz183N8A2d"
    crossorigin="anonymous">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/styles/github.min.css"
    integrity="sha384-eFTL69TLRZTkNfYZOLM+G04821K1qZao/4QLJbet1pP4tcF+fdXq/9CdqAbWRl/L"
    crossorigin="anonymous">
  @stack('styles')
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
  @php
    $fileDownloadUrlTemplate = parse_url(route('files.show', ['uuid' => '__uuid__']), PHP_URL_PATH);
    $fileNavigationUrlTemplate = parse_url(route('files.navigation', ['uuid' => '__uuid__']), PHP_URL_PATH);
  @endphp
  @stack('modals')
  @parent
  <script
    src="https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.js"
    integrity="sha384-YDXeUfPZ4SP6vJpnF+ZMmf4B1bax6yd4Q/aNbkvLidRD843hPG5RE67M0IYT4LOq"
    crossorigin="anonymous"></script>
  <script
    src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/highlight.min.js"
    integrity="sha384-RH2xi4eIQ/gjtbs9fUXM68sLSi99C7ZWBRX1vDrVv6GQXRibxXLbwO2NGZB74MbU"
    crossorigin="anonymous"></script>
  @stack('scripts')
  <script>
    window.fileDownloadUrlTemplate = @json($fileDownloadUrlTemplate);
    window.fileNavigationUrlTemplate = @json($fileNavigationUrlTemplate);
  </script>
  <script type="module" src="{{ asset('js/app.js') }}"></script>
  <script>
    // Seu código .js
  </script>
@endsection
