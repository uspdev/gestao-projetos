@php
  $modalId = $modalId ?? 'modalTaskForm';
  $selectableTaskTags =
      $selectableTaskTags ??
      (isset($availableTaskTags) ? $availableTaskTags : \App\Models\Tag::withType('tasks')->orderBy('name')->get());
  $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
  $createAction = $createAction ?? route('projects.tasks.store', $project);
  $hasOldCreate = $errors->any() && old('_method') === null && old('title') !== null;
  $hasOldEdit = $errors->any() && old('_method') === 'PUT';
  $oldTaskId = old('task_id');
  $oldUpdateAction = $oldTaskId ? route('tasks.update', $oldTaskId) : '';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
  data-create-action="{{ $createAction }}" data-old-update-action="{{ $oldUpdateAction }}"
  data-old-task-id="{{ $oldTaskId ?? '' }}" data-has-old-create="{{ $hasOldCreate ? '1' : '0' }}"
  data-has-old-edit="{{ $hasOldEdit ? '1' : '0' }}">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $modalId }}Label" data-role="modal-title">Nova Tarefa</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="{{ $createAction }}" method="POST">
        @csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        <input type="hidden" name="task_id" value="{{ $oldTaskId ?? '' }}">
        <input type="hidden" name="action" value="{{ url()->current() }}">

        <div class="modal-body">
          <div class="row">
            <div class="col-12">
              <x-form.input name="title" label="Título da Tarefa" value="{{ old('title') }}" required minlength="3"
                maxlength="120" />
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group mb-3">
                <label for="status">Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror"
                  required>
                  @foreach (\App\Enums\Task\TaskStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ old('status') == $status->value ? 'selected' : '' }}>
                      {{ $status->label() }}
                    </option>
                  @endforeach
                </select>
                @error('status')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group mb-3">
                <label for="priority">Prioridade</label>
                <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror">
                  <option value="">Selecione...</option>
                  @foreach (\App\Enums\Task\TaskPriority::cases() as $priority)
                    <option value="{{ $priority->value }}"
                      {{ old('priority') == $priority->value ? 'selected' : '' }}>
                      {{ $priority->label() }}
                    </option>
                  @endforeach
                </select>
                @error('priority')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group mb-3">
                <label>Tags</label>
                <select name="tags[]" multiple style="width: 100%;"
                  class="form-control select2-tags @error('tags') is-invalid @enderror @error('tags.*') is-invalid @enderror">
                  @foreach ($selectableTaskTags as $tag)
                    <option value="{{ $tag->id }}"
                      {{ in_array($tag->id, $selectedTags, true) ? 'selected' : '' }}>
                      {{ $tag->name }}
                    </option>
                  @endforeach
                </select>
                @error('tags')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @error('tags.*')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <x-form.input type="date" name="start_date" label="Data de Início" value="{{ old('start_date') }}" />
            </div>
            <div class="col-md-6">
              <x-form.input type="date" name="due_date" label="Data de Entrega (Prazo)"
                value="{{ old('due_date') }}" />
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <x-form.textarea name="description" label="Descrição Detalhada" value="{{ old('description') }}"
                rows="4" maxlength="10000" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success" data-role="submit-btn">
            <i class="fas fa-save"></i> Criar Tarefa
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@once
  @section('javascripts_bottom')
    @parent
    @include('tasks.partials.multi-select-script')

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('{{ $modalId }}');
        if (!modal) return;

        var form = modal.querySelector('form');
        var methodInput = form.querySelector('input[name="_method"]');
        var taskIdInput = form.querySelector('input[name="task_id"]');
        var titleInput = form.querySelector('[name="title"]');
        var statusSelect = form.querySelector('[name="status"]');
        var prioritySelect = form.querySelector('[name="priority"]');
        var startDateInput = form.querySelector('[name="start_date"]');
        var dueDateInput = form.querySelector('[name="due_date"]');
        var descriptionInput = form.querySelector('[name="description"]');
        var tagsSelect = form.querySelector('[name="tags[]"]');
        var modalTitle = modal.querySelector('[data-role="modal-title"]');
        var submitBtn = modal.querySelector('[data-role="submit-btn"]');

        // Função para definir o valor selecionado em um campo de seleção simples
        function setSelectValue(select, value) {
          if (!select) return;
          var options = Array.prototype.slice.call(select.options || []);
          options.forEach(function(option) {
            option.selected = String(option.value) === String(value || '');
          });
        }
        // Função para definir os valores selecionados em um campo de múltipla seleção, como o de tags
        function setMultiSelect(select, values) {
          if (!select) return;
          var normalized = (values || []).map(function(value) {
            return String(value);
          });
          // Itera sobre as opções do select e marca como selecionada aquelas cujo valor está presente no array de valores fornecido
          Array.prototype.slice.call(select.options || []).forEach(function(option) {
            option.selected = normalized.indexOf(String(option.value)) !== -1;
          });

          if (window.jQuery) {
            var $select = window.jQuery(select);
            if ($select.data('select2')) {
              $select.trigger('change');
            }
          }
        }
        // Função para decodificar entidades HTML em uma string,
        // garantindo que caracteres especiais sejam exibidos corretamente no formulário de edição
        function decodeHtmlEntities(value) {
          if (value === null || value === undefined) {
            return '';
          }

          var current = String(value);

          for (var i = 0; i < 5; i++) {
            var textarea = document.createElement('textarea');
            textarea.innerHTML = current;

            var decoded = textarea.value;
            if (decoded === current) {
              return decoded;
            }

            current = decoded;
          }

          return current;
        }
        // Função para preparar o formulário para criação, definindo a ação e limpando os campos relevantes
        function prepareCreate(action) {
          if (!form) return;
          form.action = action || modal.dataset.createAction || form.action;
          if (methodInput) {
            methodInput.disabled = true;
          }
          if (taskIdInput) {
            taskIdInput.value = '';
          }
          if (modalTitle) {
            modalTitle.textContent = 'Nova Tarefa';
          }
          if (submitBtn) {
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-success');
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Criar Tarefa';
          }
        }
        // Função para resetar o formulário para o estado de criação, limpando os campos e definindo a ação correta
        function resetToCreate(action) {
          prepareCreate(action);
          if (titleInput) {
            titleInput.value = '';
          }
          if (statusSelect && statusSelect.options.length) {
            statusSelect.selectedIndex = 0;
          }
          if (prioritySelect) {
            setSelectValue(prioritySelect, '');
          }
          if (startDateInput) {
            startDateInput.value = '';
          }
          if (dueDateInput) {
            dueDateInput.value = '';
          }
          if (descriptionInput) {
            descriptionInput.value = '';
          }
          if (tagsSelect) {
            setMultiSelect(tagsSelect, []);
          }
        }
        // Função para preencher o formulário com os dados da tarefa a ser editada
        function applyEditData(data) {
          form.action = data.action || form.action;
          if (methodInput) {
            methodInput.disabled = false;
            methodInput.value = 'PUT';
          }
          if (taskIdInput) {
            taskIdInput.value = data.taskId || '';
          }
          if (titleInput && data.title !== undefined) {
            titleInput.value = data.title;
          }
          if (statusSelect && data.status !== undefined) {
            setSelectValue(statusSelect, data.status);
          }
          if (prioritySelect && data.priority !== undefined) {
            setSelectValue(prioritySelect, data.priority);
          }
          if (startDateInput && data.startDate !== undefined) {
            startDateInput.value = data.startDate || '';
          }
          if (dueDateInput && data.dueDate !== undefined) {
            dueDateInput.value = data.dueDate || '';
          }
          if (descriptionInput && data.description !== undefined) {
            descriptionInput.value = decodeHtmlEntities(data.description || '');
          }
          if (tagsSelect && data.tags !== undefined) {
            setMultiSelect(tagsSelect, data.tags || []);
          }
          if (modalTitle) {
            var title = data.title ? 'Editar Tarefa: ' + data.title : 'Editar Tarefa';
            modalTitle.textContent = title;
          }
          if (submitBtn) {
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-primary');
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
          }
        }
        // Adiciona um listener de clique a todos os botões que devem abrir o modal para criação ou edição de tarefas,
        // utilizando os atributos data-* para determinar o modo e os dados a serem preenchidos no formulário
        document.querySelectorAll('[data-task-modal="task-form"]').forEach(function(button) {
          button.addEventListener('click', function() {
            var mode = button.getAttribute('data-mode') || 'create';
            var action = button.getAttribute('data-action') || '';

            if (mode === 'edit') {
              var tagsRaw = button.getAttribute('data-tags');
              var tags = [];
              if (tagsRaw) {
                try {
                  tags = JSON.parse(tagsRaw);
                } catch (error) {
                  tags = tagsRaw.split(',').filter(Boolean).map(function(value) {
                    return Number(value);
                  });
                }
              }

              applyEditData({
                action: action,
                taskId: button.getAttribute('data-task-id'),
                title: button.getAttribute('data-title') || '',
                status: button.getAttribute('data-status') || '',
                priority: button.getAttribute('data-priority') || '',
                startDate: button.getAttribute('data-start-date') || '',
                dueDate: button.getAttribute('data-due-date') || '',
                description: button.getAttribute('data-description') || '',
                tags: tags
              });
            } else {
              resetToCreate(action || modal.dataset.createAction || form.action);
            }
          });
        });
        // Ao carregar a página, verifica se há dados de criação ou
        // edição pendentes (por exemplo, após uma validação falhada) e prepara o modal para exibir esses dados,
        // abrindo-o automaticamente se necessário
        if (modal.dataset.hasOldCreate === '1') {
          prepareCreate(modal.dataset.createAction);
          if (window.jQuery) {
            window.jQuery(modal).modal('show');
          }
        }
        // Se houver dados de edição pendentes, preenche o formulário com esses dados
        // e exibe o modal para que o usuário possa corrigir os erros e salvar as alterações
        if (modal.dataset.hasOldEdit === '1') {
          applyEditData({
            action: modal.dataset.oldUpdateAction,
            taskId: modal.dataset.oldTaskId || ''
          });
          if (window.jQuery) {
            window.jQuery(modal).modal('show');
          }
        }
      });
    </script>
  @endsection
@endonce
