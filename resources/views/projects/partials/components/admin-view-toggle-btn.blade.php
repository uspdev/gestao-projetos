@if (auth()->user()->isAdmin())
  <form method="POST" action="{{ route('admin.view-toggle') }}" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-sm {{ $viewAll ? 'btn-danger' : 'btn-outline-danger' }}"
      title="{{ $viewAll ? $allViewTitle : $myViewTitle }}">
      <i class="fas {{ $viewAll ? 'fa-eye' : 'fa-eye-slash' }}"></i>
      {{ $viewAll ? $allViewLabel : $myViewLabel }}
    </button>
  </form>
@endif
