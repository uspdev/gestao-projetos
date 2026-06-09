@php
  $user = auth()->user();
  $isPinned = $project?->isPinnedBy($user) ?? false;
@endphp

<form method="POST" action="{{ route('projects.togglePin', $project) }}" class="position-relative">
  @csrf
  @method('PATCH')

  <button type="submit" data-toggle="tooltip"
    class="pin-btn badge badge-pill {{ $isPinned ? 'badge-warning text-dark' : 'badge-secondary' }} border-0 shadow-sm"
    title="{{ $isPinned ? 'Desafixar projeto' : 'Fixar projeto' }}">
    <i class="fas fa-thumbtack"></i>
  </button>
</form>


@once
  @section('styles')
    @parent
    <style>
      .pin-btn {
        cursor: pointer;
        padding: .15rem .35rem;
        font-size: .75rem;
        line-height: 1;
        transition: opacity .2s;
      }

      .pin-btn:hover {
        opacity: .75;
      }
    </style>
  @endsection
@endonce
