<script>
  document.addEventListener('DOMContentLoaded', function() {
    const dateElements = document.querySelectorAll('.local-date');

    const userLocale = navigator.language || navigator.userLanguage;

    const formatter = new Intl.DateTimeFormat(userLocale, {
      timeZone: 'UTC',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });

    dateElements.forEach(function(el) {
      const isoDate = el.getAttribute('datetime');
      if (isoDate) {
        const dateObj = new Date(isoDate);
        el.textContent = formatter.format(dateObj);
      }
    });
  });
</script>
