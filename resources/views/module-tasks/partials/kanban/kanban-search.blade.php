{{-- os kanban-tasks precisam ter data-search que é o campo de busca --}}

@php
  // extrai o valor do status para usar como data-status
  // isso foi feito pois não existe um status "backlog" na enum,
  // por que esse status seria o a fazer sem responsável,
  // então ele é tratado como um caso especial
  $statusKey = is_object($status) && isset($status->value) ? $status->value : $status;
@endphp

<div style="position: relative;">
  <input type="text" class="form-control form-control-sm kanban-search pr-4 py-0 my-0" placeholder="🔍"
    data-status="{{ $statusKey }}">

  <span class="kanban-clear">&times;</span>
</div>

@section('styles')
  @parent
  <style>
    .kanban-search {
      width: 6ch;
      transition: width 0.2s ease;
    }

    .kanban-search:focus {
      width: 12ch;
    }

    .kanban-clear {
      position: absolute;
      right: 6px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 0.8rem;
      color: #999;
    }
  </style>
@endsection

@pushOnce('scripts')
  <script>
    // ignora acentos
    const normalize = str =>
      (str || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');

    // kanban search
    document.querySelectorAll('.kanban-search').forEach(input => {
      const wrapper = input.parentElement;
      const clear = wrapper.querySelector('.kanban-clear');
      const column = input.closest('.card');

      // estado inicial
      if (clear) clear.style.display = 'none';

      input.addEventListener('input', function() {
        const query = normalize(this.value);

        // mostrar/ocultar botão limpar
        if (clear) {
          clear.style.display = query ? 'block' : 'none';
        }

        // filtrar tasks
        column.querySelectorAll('.kanban-task').forEach(task => {
          const text = normalize(task.dataset.search || task.dataset.title || '');
          task.style.display = text.includes(query) ? '' : 'none';
        });
      });

      // ação do botão limpar
      if (clear) {
        clear.addEventListener('click', () => {
          input.value = '';
          input.dispatchEvent(new Event('input'));
          input.focus();
        });
      }
    });

    document.querySelectorAll('.kanban-clear').forEach(btn => {
      btn.addEventListener('click', function() {
        const wrapper = this.parentElement;
        const input = wrapper.querySelector('.kanban-search');

        input.value = '';
        input.dispatchEvent(new Event('input'));
        input.focus();
      });
    });
  </script>
@endPushOnce
