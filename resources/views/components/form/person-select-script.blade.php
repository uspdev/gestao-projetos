@pushOnce('scripts', 'usp-person-select')
  <script>
    window.initUspPersonSelect = function(element, options) {
      if (!window.jQuery || !window.jQuery.fn.select2) return false;

      var $element = window.jQuery(element);
      var $modal = $element.closest('.modal');
      $element.select2(window.jQuery.extend(true, {
        ajax: {
          dataType: 'json',
          delay: 1000,
          data: function(params) {
            return { term: params.term };
          },
          processResults: function(data) {
            return data;
          }
        },
        dropdownParent: $modal.length ? $modal : window.jQuery(document.body),
        minimumInputLength: 4,
        theme: 'bootstrap4',
        width: '100%',
        language: 'pt-BR',
        placeholder: 'Digite o nome ou número USP...'
      }, options));

      return true;
    };
  </script>
@endPushOnce
