@if ($files->hasPages() || $links->hasPages())
  <div class="px-3 pt-2">{{ $files->links() }}{{ $links->links() }}</div>
@endif
