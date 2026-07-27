@php
  $watchingAvailable = \Illuminate\Support\Facades\Schema::hasTable('watches');
  $watchActive = $watchingAvailable && Auth::check() && \App\Models\Watch::query()
      ->where('user_id', Auth::id())
      ->where('watchable_type', $watchable->getMorphClass())
      ->where('watchable_id', $watchable->getKey())
      ->exists();
  $watchType = $watchable->getMorphClass();
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
      class="btn btn-sm {{ $watchActive ? 'btn-outline-secondary' : 'btn-outline-primary' }}"
      type="submit"
    >
      <i class="ti ti-bell{{ $watchActive ? '-off' : '' }} mr-1"></i>
      {{ $watchActive ? 'Não receber notificações' : 'Receber notificações' }}
    </button>
  </form>
@endif
