@can('delete', $comment)
  <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="d-inline-block"
    onsubmit="return confirm('Deseja realmente apagar este comentário?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-outline-danger btn-sm py-0">
      <i class="fas fa-trash"></i>
    </button>
  </form>
@endcan
