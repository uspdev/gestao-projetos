{{--
Textarea autogrow

Uso:
- Incluir este bloco no layouts.app ou em outro layout: @include('blocos.textarea-autogrow')
- Marcar os textareas que devem crescer automaticamente com o atributo data-autogrow-textarea

Comportamento:
- Remove o resize manual do textarea
- Ajusta a altura de acordo com o conteúdo digitado
- Recalcula a altura ao carregar a página, ao receber foco e ao abrir elementos colapsados


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

          if (textarea.offsetParent === null) {
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

          if (textarea.offsetParent === null) {
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
