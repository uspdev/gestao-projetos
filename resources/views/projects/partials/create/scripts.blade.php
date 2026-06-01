@include('projects.partials.scripts.multi-select-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-project-form="create"]');
    if (!form) return;

    const nameInput = form.querySelector('input[name="name"]');
    const slugInput = form.querySelector('input[name="slug"]');

    if (!nameInput || !slugInput) return;

    const slugify = (value) => {
      return value
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
    };

    const normalizeSlugInput = () => {
      const normalized = slugify(slugInput.value);

      if (slugInput.value !== normalized) {
        slugInput.value = normalized;
      }

      if (slugInput.value.trim() === '') {
        slugInput.setCustomValidity('Informe um slug valido usando letras minusculas, numeros e hifens.');
        return;
      }

      slugInput.setCustomValidity('');
    };

    let isSlugDirty = false;

    if (slugInput.value.trim() !== '' && slugInput.value !== slugify(nameInput.value)) {
      isSlugDirty = true;
    }

    slugInput.addEventListener('input', function() {
      isSlugDirty = true;
      normalizeSlugInput();
    });

    slugInput.addEventListener('blur', function() {
      slugInput.reportValidity();
    });

    nameInput.addEventListener('input', function() {
      if (isSlugDirty) {
        return;
      }

      slugInput.value = slugify(nameInput.value);
      normalizeSlugInput();
    });

    normalizeSlugInput();
  });
</script>
