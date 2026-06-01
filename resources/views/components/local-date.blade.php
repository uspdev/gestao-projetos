@props([
    'date' => null,
    'overdue' => false,
    'empty' => '--/--/----',
    'showTime' => false,
])
<span
  {{ $attributes->class(['text-danger font-weight-bold' => $overdue, 'text-dark' => !$overdue]) }}>
  <time class="local-date{{ $showTime ? ' local-datetime' : '' }}"
        datetime="{{ $date?->toDateString() }}{{ $showTime && $date ? 'T' . $date->format('H:i') : '' }}">
    {{ $date?->toDateString() ?? $empty }}
  </time>
</span>
@pushOnce('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const dateFormatter = new Intl.DateTimeFormat(
        navigator.language || 'pt-BR', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
        }
      );

      const datetimeFormatter = new Intl.DateTimeFormat(
        navigator.language || 'pt-BR', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
        }
      );

      document.querySelectorAll('.local-date').forEach(el => {
        if (!el.dateTime) return;
        const isDatetime = el.classList.contains('local-datetime');
        // evita bug de timezone em datas puras
        const dateObj = isDatetime
          ? new Date(el.dateTime)
          : new Date(`${el.dateTime}T00:00:00`);
        el.textContent = isDatetime
          ? datetimeFormatter.format(dateObj)
          : dateFormatter.format(dateObj);
      });
    });
  </script>
@endPushOnce
