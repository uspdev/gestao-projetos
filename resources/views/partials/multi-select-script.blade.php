<script>
  $(document).ready(function() {
      $('.select2-tags').each(function() {
          var $element = $(this);
          var $modal = $element.closest('.modal'); 

          $element.select2({
              placeholder: "Selecione as tags...",
              allowClear: true,
              dropdownParent: $modal.length ? $modal : $(document.body),
              width: '100%' 
          });
      });

      // Resolve um bug comum do Bootstrap Modal que impede o foco no campo de busca do Select2
      if ($.fn.modal) {
          $.fn.modal.Constructor.prototype._enforceFocus = function() {};
      }
  });
</script>