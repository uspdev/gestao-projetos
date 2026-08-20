@can('create', [\App\Models\Link::class, $owner])
  @push('modals')
    <div class="modal fade" id="{{ $linkModalId }}" tabindex="-1" role="dialog"
      aria-labelledby="{{ $linkModalId }}-title" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="{{ $linkRoute }}" method="post" data-disable-client-validation>@csrf<div
              class="modal-header">
              <h2 class="modal-title h5" id="{{ $linkModalId }}-title">Adicionar links</h2><button type="button"
                class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><label for="{{ $componentId }}-urls">Cole uma URL por linha</label>
              <textarea id="{{ $componentId }}-urls" name="urls" class="form-control @error('urls') is-invalid @enderror"
                rows="7" required placeholder="https://exemplo.org/documento&#10;https://exemplo.org/site">{{ old('urls') }}</textarea>
              @error('urls')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary"
                data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Adicionar
                links</button></div>
          </form>
        </div>
      </div>
    </div>
  @endpush
  @if ($errors->has('urls'))
    @push('scripts')
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          if (window.jQuery) window.jQuery('#{{ $linkModalId }}').modal('show');
        });
      </script>
    @endpush
  @endif
@endcan
