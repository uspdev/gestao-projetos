@php
  $showLabel ??= false;
  $watchingAvailable = \Illuminate\Support\Facades\Schema::hasTable('watches');
  $watchActive = $watchingAvailable && Auth::check() && \App\Models\Watch::query()
      ->where('user_id', Auth::id())
      ->where('watchable_type', $watchable->getMorphClass())
      ->where('watchable_id', $watchable->getKey())
      ->exists();
  $watchType = $watchable->getMorphClass();
  $watchActionLabel = $watchActive ? 'Desativar notificações' : 'Ativar notificações';
  $watchStateLabel = $watchActive ? 'Notificações ativas' : 'Notificações inativas';
@endphp

@if ($watchingAvailable && Auth::check() && $watchable->watchCanBeViewedBy(Auth::user()))
  <form
    method="POST"
    action="{{ $watchActive
        ? route('watches.destroy', [$watchType, $watchable->getKey()])
        : route('watches.update', [$watchType, $watchable->getKey()]) }}"
    class="d-inline-block"
  >
    @csrf
    @method($watchActive ? 'DELETE' : 'PUT')
    <button
      class="btn btn-sm {{ $showLabel ? ($watchActive ? 'btn-outline-secondary' : 'btn-outline-primary') : 'p-1 border-0 bg-transparent ' . ($watchActive ? 'text-primary' : 'text-muted') }}"
      type="submit"
      title="{{ $watchStateLabel }}. {{ $watchActionLabel }}."
      aria-label="{{ $watchStateLabel }}. {{ $watchActionLabel }}."
      aria-pressed="{{ $watchActive ? 'true' : 'false' }}"
    >
      <i class="fas fa-bell{{ $watchActive ? '' : '-slash' }} {{ $showLabel ? 'mr-1' : '' }}" aria-hidden="true"></i>
      @if ($showLabel)
        {{ $watchActionLabel }}
      @endif
    </button>
  </form>
@endif
