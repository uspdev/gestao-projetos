@php
  $modalId = $modalId ?? 'modalTaskForm';
  $selectableTaskTags = $availableTaskTags ?? \App\Models\Tag::forTasks();
  $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
  $selectableTaskAssignees = $availableTaskAssignees
      ?? \App\Models\User::assignableToProject($project->id)->get(['users.id', 'users.name', 'users.email']);
  $createAction = $createAction ?? route('projects.tasks.store', $project);
  $hasOldCreate = $errors->any() && old('_method') === null && old('title') !== null;
  $hasOldEdit = $errors->any() && old('_method') === 'PATCH' && old('title') !== null;
  $oldTaskId = old('task_id');
  $oldUpdateAction = $oldTaskId ? route('tasks.updateInfo', $oldTaskId) : '';
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
        <input type="hidden" name="_method" value="PATCH" disabled>
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
            <div class="col-12" data-role="create-assignee-field">
              <div class="form-group mb-3">
                <label for="task-assignee-id">Responsável <span class="text-muted">(opcional)</span></label>
                <select name="assignee_id" id="task-assignee-id"
                  class="form-control @error('assignee_id') is-invalid @enderror">
                  <option value="">Sem responsável</option>
                  @foreach ($selectableTaskAssignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected((int) old('assignee_id') === (int) $assignee->id)>
                      {{ $assignee->name }} ({{ $assignee->email }})
                    </option>
                  @endforeach
                </select>
                @error('assignee_id')
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

          {{-- Descrição movida para modal específico de edição de descrição --}}
        </div>

        <div class="modal-footer">
          <x-form.cancel-button data-dismiss="modal" />
          <x-form.save-button class="btn btn-success" data-role="submit-btn" label="Criar Tarefa" />
        </div>
      </form>
    </div>
  </div>
</div>

@pushOnce('scripts')
  @include('module-tasks.partials.scripts.multi-select-script')

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var modal = document.getElementById('{{ $modalId }}');
      if (!modal) return;

      var form = modal.querySelector('form');
      var methodInput = form.querySelector('input[name="_method"]');
      var taskIdInput = form.querySelector('input[name="task_id"]');
      var titleInput = modal.querySelector('[name="title"]');
      var statusSelect = form.querySelector('[name="status"]');
      var prioritySelect = form.querySelector('[name="priority"]');
      var startDateInput = form.querySelector('[name="start_date"]');
      var dueDateInput = form.querySelector('[name="due_date"]');
      var descriptionInput = null;
      var tagsSelect = form.querySelector('[name="tags[]"]');
      var assigneeField = form.querySelector('[data-role="create-assignee-field"]');
      var assigneeSelect = form.querySelector('[name="assignee_id"]');
      var modalTitle = modal.querySelector('[data-role="modal-title"]');
      var submitBtn = modal.querySelector('[data-role="submit-btn"]');

      function setSelectValue(select, value) {
        if (!select) return;
        var options = Array.prototype.slice.call(select.options || []);
        options.forEach(function(option) {
          option.selected = String(option.value) === String(value || '');
        });
      }

      function setMultiSelect(select, values) {
        if (!select) return;
        var normalized = (values || []).map(function(value) {
          return String(value);
        });
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

      function prepareCreate(action) {
        if (!form) return;
        form.action = action || modal.dataset.createAction || form.action;
        if (methodInput) {
          methodInput.disabled = true;
        }
        if (taskIdInput) {
          taskIdInput.value = '';
        }
        if (assigneeField) {
          assigneeField.classList.remove('d-none');
        }
        if (assigneeSelect) {
          assigneeSelect.disabled = false;
        }
        if (modalTitle) {
          modalTitle.textContent = 'Nova Tarefa';
        }
        if (submitBtn) {
          submitBtn.classList.remove('btn-primary');
          submitBtn.classList.add('btn-success');
          submitBtn.innerHTML =
            '<i class="fas fa-save" aria-hidden="true"></i><span class="sr-only">Criar Tarefa</span>';
        }
      }

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
        if (assigneeSelect) {
          setSelectValue(assigneeSelect, '');
        }
      }

      function applyEditData(data) {
        form.action = data.action || form.action;
        if (methodInput) {
          methodInput.disabled = false;
          methodInput.value = 'PATCH';
        }
        if (taskIdInput) {
          taskIdInput.value = data.taskId || '';
        }
        if (assigneeField) {
          assigneeField.classList.add('d-none');
        }
        if (assigneeSelect) {
          assigneeSelect.disabled = true;
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
        // descrição gerenciada em modal separado
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
          submitBtn.innerHTML =
            '<i class="fas fa-save" aria-hidden="true"></i><span class="sr-only">Salvar Alterações</span>';
        }
      }
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
      if (modal.dataset.hasOldCreate === '1') {
        prepareCreate(modal.dataset.createAction);
        if (window.jQuery) {
          window.jQuery(modal).modal('show');
        }
      }
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
@endPushOnce
