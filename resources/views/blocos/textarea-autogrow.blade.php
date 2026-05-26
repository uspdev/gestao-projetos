{{--
Datatables, botoes excel e csv, sem paginação, topo em 1 linha, alinhado esquerda

Uso:
- Incluir no layouts.app ou em outro lugar: @include('laravel-usp-theme::blocos.datatable-simples')
- Adiconar a classe: <table class="... datatable-simples">
- se quiser passar algo adicional no menu do datatables, passar $dtSlot com o conteúdo desejado:
    $dtSlot = view('partials.dt-slot')->render();
    return view('sua-view')->compact('variaveis', 'dtSlot'));

Classes de modificação:
- 'dt-fixed-header': ativa o fixed header
- 'dt-paging-10' ou 'dt-paging-50': ativa paginação com 10 ou 50 por página
- 'dt-buttons': ativa os botões de excel e csv
- 'dt-button-pdf' e 'dt-button pdf-landscape': se 'dt-buttons', inclui botão para pdf ou pdf-landscape
- 'dt-state-save': salva o estado da tabela no localStorage

@author Masakik, em 23/3/2023
@author Masakik, em 25/4/2023, incluindo classes de modificação
@author Masakik, em 21/9/2023, incluindo classes dt-button-pdf e dt-button-pdf-landscape #115
@author Masakik, em 10/5/2024, fixed header abaixo de card-header-sticky se houver
@author Masakik, em 03/7/2025, adicionado a opção $dtSlot
@author Masakik, em 11/08/2025, salva o estado da tabela no localStorage
--}}

@pushOnce('scripts')
  <script>
    // Auto-grow textareas
    function resizeAutogrowTextarea(textarea) {
      textarea.style.height = "auto";
      textarea.style.height = textarea.scrollHeight + "px";
    }

    function initAutogrowTextareas() {
      document
        .querySelectorAll("[data-autogrow-textarea]")
        .forEach(function(textarea) {
          if (textarea.dataset.autogrowInitialized === "true") {
            return;
          }

          textarea.dataset.autogrowInitialized = "true";
          textarea.addEventListener("input", function() {
            resizeAutogrowTextarea(textarea);
          });
          resizeAutogrowTextarea(textarea);
        });
    }

    document.addEventListener("DOMContentLoaded", initAutogrowTextareas);
    (function() {
      function resizeAutogrowTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
      }

      function initAutogrowTextareas(root) {
        root.querySelectorAll('[data-autogrow-textarea]').forEach(function(textarea) {
          if (textarea.dataset.autogrowInitialized === 'true') {
            return;
          }

          textarea.dataset.autogrowInitialized = 'true';
          textarea.addEventListener('input', function() {
            resizeAutogrowTextarea(textarea);
          });
          textarea.addEventListener('focus', function() {
            resizeAutogrowTextarea(textarea);
          });

          if (textarea.offsetParent !== null) {
            resizeAutogrowTextarea(textarea);
          }
        });
      }

      document.addEventListener('DOMContentLoaded', function() {
        initAutogrowTextareas(document);
      });

      document.addEventListener('shown.bs.collapse', function(event) {
        initAutogrowTextareas(event.target);
      });
    })
    ();
  </script>
@endpushOnce
