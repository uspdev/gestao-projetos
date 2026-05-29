@php
  $modalId = $modalId ?? 'modalTaskDescription';
  $oldTaskId = old('task_id');
  $oldUpdateAction = $oldTaskId ? route('tasks.updateDescription', $oldTaskId) : '';
  $createAction = $createAction ?? '';
  $hasOldEdit = $errors->any() && old('_method') === 'PATCH' && old('description') !== null && old('title') === null;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
  data-old-update-action="{{ $oldUpdateAction }}" data-old-task-id="{{ $oldTaskId ?? '' }}"
  data-has-old-edit="{{ $hasOldEdit ? '1' : '0' }}">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $modalId }}Label" data-role="modal-title">Editar Descrição</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="" method="POST">
        @csrf
        <input type="hidden" name="_method" value="PATCH" disabled>
        <input type="hidden" name="task_id" value="{{ $oldTaskId ?? '' }}">

        <div class="modal-body">
          <div class="row">
            <div class="col-12">
              <x-form.textarea name="description" label="Descrição Detalhada" value="{{ old('description') }}"
                rows="6" maxlength="10000" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <x-form.cancel-button data-dismiss="modal" />
          <x-form.save-button class="btn btn-primary" data-role="submit-btn" />
        </div>
      </form>
    </div>
  </div>
</div>

@once
  @section('javascripts_bottom')
    @parent
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('{{ $modalId }}');
        if (!modal) return;

        var form = modal.querySelector('form');
        var methodInput = form.querySelector('input[name="_method"]');
        var taskIdInput = form.querySelector('input[name="task_id"]');
        var descriptionInput = form.querySelector('[name="description"]');
        var modalTitle = modal.querySelector('[data-role="modal-title"]');
        var submitBtn = modal.querySelector('[data-role="submit-btn"]');

        function applyEditData(data) {
          form.action = data.action || form.action;
          if (methodInput) {
            methodInput.disabled = false;
            methodInput.value = 'PATCH';
          }
          if (taskIdInput) {
            taskIdInput.value = data.taskId || '';
          }
          if (descriptionInput && data.description !== undefined) {
            descriptionInput.value = data.description || '';
          }
          if (modalTitle) {
            var title = data.title ? 'Editar Descrição: ' + data.title : 'Editar Descrição';
            modalTitle.textContent = title;
          }
        }

        document.querySelectorAll('[data-task-modal="task-description-form"]').forEach(function(button) {
          button.addEventListener('click', function() {
            var action = button.getAttribute('data-action') || '';
            applyEditData({
              action: action,
              taskId: button.getAttribute('data-task-id'),
              title: button.getAttribute('data-title') || '',
              description: button.getAttribute('data-description') || ''
            });
          });
        });

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
