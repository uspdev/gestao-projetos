@if (session('tasks_view') === 'kanban' && request()->routeIs('projects.tasks.index'))
  <div class="d-flex align-items-center ml-4 py-0 gap-2">
    <input id="task-search" type="search" class="form-control form-control-sm py-0" placeholder="Buscar tarefa"
      style="width: 200px; height: 24px;" autofocus>
    <button id="task-search-clear" type="button" class="btn btn-outline-secondary btn-sm py-0">Limpar</button>
  </div>
  @push('scripts')
    <script>
      (function() {
        const input = document.getElementById('task-search');
        const clearBtn = document.getElementById('task-search-clear');
        const noResults = document.getElementById('tasks-no-results');

        function normalize(s) {
          return (s || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
        }

        function getItems() {
          return Array.from(document.querySelectorAll('[data-task-searchable], .kanban-task'));
        }

        function applyFilter() {
          const q = normalize(input.value).trim();
          const items = getItems();
          let visible = 0;

          items.forEach(item => {
            const hay = normalize(item.getAttribute('data-task-searchable') || item.dataset.search || item.dataset
              .title || '');
            const show = q === '' || hay.includes(q);
            item.style.display = show ? '' : 'none';
            if (show) visible++;
          });

          if (noResults) {
            noResults.classList.toggle('d-none', items.length === 0 || visible !== 0);
          }

          if (window.updateProjectTasksCount) {
            window.updateProjectTasksCount();
          }
        }

        if (clearBtn) {
          clearBtn.addEventListener('click', function() {
            input.value = '';
            input.focus();
            applyFilter();
          });
        }

        input && input.addEventListener('input', applyFilter);

        if (input) {
          applyFilter();
        }
      })();
    </script>
  @endpush
@endif
