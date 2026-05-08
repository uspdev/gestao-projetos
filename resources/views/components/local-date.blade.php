@props([
    'date' => null,
    'empty' => '--/--/----',
])

<span {{ $attributes->class('font-weight-bold') }}>
  <time class="local-date" datetime="{{ $date?->toDateString() }}">
    {{ $date?->toDateString() ?? $empty }}
  </time>
</span>

@pushOnce('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {

      const formatter = new Intl.DateTimeFormat(
        navigator.language || 'pt-BR', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
        }
      );

      document.querySelectorAll('.local-date').forEach(el => {

        if (!el.dateTime) return;

        // evita bug de timezone
        const dateObj = new Date(`${el.dateTime}T00:00:00`);

        el.textContent = formatter.format(dateObj);
      });
    });
  </script>
@endPushOnce
