<div class="d-flex align-items-center py-0 gap-2">
  <input id="subproject-search" type="search" class="form-control form-control-sm py-0" placeholder="Buscar subprojeto" style="width: 200px; height: 24px;">
</div>

@push('scripts')
  <script>
    (function() {
      const input = document.getElementById('subproject-search');
      const noResults = document.getElementById('subprojects-no-results');

      if (!input) return;

      function normalize(s) {
        return (s || '')
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '');
      }

      function applyFilter() {
        const q = normalize(input.value).trim();
        const items = Array.from(document.querySelectorAll('[data-subproject-searchable]'));
        let visible = 0;

        items.forEach(item => {
          const hay = normalize(item.getAttribute('data-subproject-searchable'));
          const show = q === '' || hay.includes(q);
          
          item.style.display = show ? '' : 'none';
          
          if (show) visible++;
        });

        if (noResults) {
          noResults.classList.toggle('d-none', items.length === 0 || visible !== 0);
        }
      }

      input.addEventListener('input', applyFilter);
      applyFilter();
    })();
  </script>
@endpush