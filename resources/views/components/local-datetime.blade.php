@props([
    'date' => null,
    'empty' => '--/--/---- --:--',
])

<time {{ $attributes->class('local-datetime') }} datetime="{{ $date?->toIso8601String() }}">
  {{ $date?->format('d/m/Y H:i') ?? $empty }}
</time>

@pushOnce('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const formatter = new Intl.DateTimeFormat(
        navigator.language || 'pt-BR', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
        }
      );

      document.querySelectorAll('.local-datetime').forEach(el => {
        if (!el.dateTime) return;

        const dateObj = new Date(el.dateTime);

        if (Number.isNaN(dateObj.getTime())) return;

        el.textContent = formatter.format(dateObj);
      });
    });
  </script>
@endPushOnce
