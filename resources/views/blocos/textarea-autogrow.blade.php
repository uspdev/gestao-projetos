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

@push('styles')
  <style>
    textarea[data-autogrow-textarea] {
      resize: none;
      overflow: hidden;
    }
  </style>
@endPush


@pushOnce('scripts')
  <script>
    (function() {
      var AUTOGROW_SELECTOR = '[data-autogrow-textarea]';

      function getScrollableParent(element) {
        var current = element ? element.parentElement : null;

        while (current && current !== document.body) {
          var style = window.getComputedStyle(current);
          var overflowY = style ? style.overflowY : '';

          if ((overflowY === 'auto' || overflowY === 'scroll') && current.scrollHeight > current.clientHeight) {
            return current;
          }

          current = current.parentElement;
        }

        return null;
      }

      function captureScrollState(textarea) {
        var state = {
          windowTop: window.pageYOffset,
          windowLeft: window.pageXOffset,
          parent: null,
          parentTop: 0,
        };

        var scrollParent = getScrollableParent(textarea);
        if (scrollParent) {
          state.parent = scrollParent;
          state.parentTop = scrollParent.scrollTop;
        }

        return state;
      }

      function restoreScrollState(state) {
        if (!state) return;

        if (window.pageYOffset !== state.windowTop || window.pageXOffset !== state.windowLeft) {
          window.scrollTo(state.windowLeft, state.windowTop);
        }

        if (state.parent && state.parent.scrollTop !== state.parentTop) {
          state.parent.scrollTop = state.parentTop;
        }
      }

      function resizeTextarea(textarea) {
        var scrollState = captureScrollState(textarea);
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        restoreScrollState(scrollState);
      }

      function withTransitionDisabled(textarea, callback) {
        var previousTransition = textarea.style.transition;
        textarea.style.transition = 'none';
        callback();
        requestAnimationFrame(function() {
          textarea.style.transition = previousTransition || '';
        });
      }

      function ensureInitialized(textarea) {
        if (textarea.dataset.autogrowInitialized === 'true') {
          return;
        }

        textarea.dataset.autogrowInitialized = 'true';
        textarea.addEventListener('input', function() {
          resizeTextarea(textarea);
        });
        textarea.addEventListener('focus', function() {
          resizeTextarea(textarea);
        });
      }

      function updateTextarea(textarea, force) {
        if (!force && textarea.offsetParent === null) {
          return;
        }

        ensureInitialized(textarea);

        if (force) {
          withTransitionDisabled(textarea, function() {
            resizeTextarea(textarea);
          });
          return;
        }

        resizeTextarea(textarea);
      }

      function initAutogrowTextareas(root, options) {
        if (!root || typeof root.querySelectorAll !== 'function') {
          return;
        }

        var force = Boolean(options && options.force);

        root.querySelectorAll(AUTOGROW_SELECTOR).forEach(function(textarea) {
          updateTextarea(textarea, force);
        });
      }

      function handleCollapseShown(event) {
        initAutogrowTextareas(event.target, {
          force: true
        });
      }

      document.addEventListener('DOMContentLoaded', function() {
        initAutogrowTextareas(document, {
          force: false
        });
      });

      if (window.jQuery) {
        window.jQuery(document).on('shown.bs.collapse', handleCollapseShown);
      } else {
        document.addEventListener('shown.bs.collapse', handleCollapseShown);
      }
    })
    ();
  </script>
@endpushOnce
