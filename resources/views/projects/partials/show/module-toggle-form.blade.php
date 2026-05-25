@php
  $actionLabel = $module['enabled'] ? 'desativar' : 'ativar';
  $confirmMessage = "Tem certeza que deseja {$actionLabel} o modulo \"{$module['name']}\"? Se tiver certeza do que esta fazendo, confirme.";
@endphp

<form method="POST" action="{{ route('projects.modules.update', [$project, $module['slug']]) }}"
  onsubmit='return confirm(@json($confirmMessage));'>
  @csrf
  @method('PATCH')
  <input type="hidden" name="enabled" value="{{ $module['enabled'] ? 0 : 1 }}">
  <button type="submit" class="btn btn-sm {{ $module['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }}"
    @if ($module['toggleLocked']) disabled @endif>
    {{ $module['enabled'] ? 'Desativar' : 'Ativar' }}
  </button>
</form>
