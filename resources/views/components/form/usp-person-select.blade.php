@props([
  'name',
  'label',
  'url',
  'id' => null,
  'value' => '',
  'valueLabel' => null,
  'valueType' => 'id',
  'allowFreeText' => false,
  'required' => false,
  'autoOpen' => false,
  'placeholder' => 'Digite o nome ou número USP...',
  'help' => null,
  'labelClass' => '',
  'queryFields' => [],
  'maxlength' => 255,
])
@php
  $id = $id ?? 'usp-person-' . \Illuminate\Support\Str::uuid();
  $selectedValue = (string) old($name, $value);
  $selectedLabel = $valueLabel !== null && $selectedValue === (string) $value ? $valueLabel : $selectedValue;
  $isRequired = $required || $attributes->has('required');
@endphp

<div class="form-group mb-3">
  <label for="{{ $id }}" class="{{ $labelClass }}">{{ $label }}</label>
  <select id="{{ $id }}" name="{{ $name }}" style="width: 100%;"
    data-usp-person-select data-search-url="{{ $url }}" data-value-type="{{ $valueType }}"
    data-allow-free-text="{{ $allowFreeText ? '1' : '0' }}" data-auto-open="{{ $autoOpen ? '1' : '0' }}"
    data-placeholder="{{ $placeholder }}" data-maxlength="{{ $maxlength }}"
    data-query-fields="{{ json_encode($queryFields) }}"
    data-resolve-initial-label="{{ $valueType === 'id' && $valueLabel === null ? '1' : '0' }}"
    @if ($help) aria-describedby="{{ $id }}-help" @endif
    @if ($errors->has($name)) aria-invalid="true" @endif
    {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }} @required($isRequired)>
    <option value=""></option>
    @if ($selectedValue !== '')
      <option value="{{ $selectedValue }}" selected>{{ $selectedLabel }}</option>
    @endif
  </select>
  @error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
  @if ($help)
    <small id="{{ $id }}-help" class="form-text text-muted">{{ $help }}</small>
  @endif
</div>

@pushOnce('scripts', 'usp-person-select')
  <script data-usp-person-select-script>
    (function () {
      var states = new WeakMap();

      function stateFor(element) {
        if (!states.has(element)) states.set(element, { pendingText: null });
        return states.get(element);
      }

      function setValue(element, value, label) {
        if (!element) return;
        stateFor(element).pendingText = null;
        element.options.length = 0;
        element.add(new Option('', ''));
        if (value !== null && value !== undefined && String(value) !== '') {
          element.add(new Option(label === undefined ? value : label, value, true, true));
        }
        if (window.jQuery) window.jQuery(element).trigger('change');
        else element.dispatchEvent(new Event('change', { bubbles: true }));
      }

      window.UspPersonSelect = { setValue: setValue };

      function bindField(element) {
        if (!window.jQuery) return;
        var $element = window.jQuery(element);
        var $modal = $element.closest('.modal');
        var state = stateFor(element);
        var freeText = element.dataset.allowFreeText === '1';
        var queryFields = JSON.parse(element.dataset.queryFields);

        function requestData(term) {
          var data = { term: term };
          Object.keys(queryFields).forEach(function (key) {
            var field = element.form && element.form.elements.namedItem(queryFields[key]);
            if (field && !field.disabled) data[key] = field.value;
          });
          return data;
        }

        function commitText() {
          if (!element.disabled && state.pendingText !== null) setValue(element, state.pendingText);
        }

        function initialize() {
          if (!window.jQuery.fn.select2) return;
          if ($element.data('select2')) return;

          $element.select2({
            ajax: {
              url: element.dataset.searchUrl,
              dataType: 'json',
              delay: 1000,
              data: function (params) { return requestData(params.term); },
              processResults: function (data) {
                return {
                  results: (data.results || []).map(function (person) {
                    return {
                      id: element.dataset.valueType === 'text' ? person.text : person.id,
                      text: person.text
                    };
                  })
                };
              }
            },
            dropdownParent: $modal.length ? $modal : window.jQuery(document.body),
            minimumInputLength: freeText ? 0 : 4,
            theme: 'bootstrap4',
            width: '100%',
            language: 'pt-BR',
            placeholder: element.dataset.placeholder,
            allowClear: !element.required,
            tags: freeText,
            createTag: function (params) {
              var text = params.term.trim();
              if (!freeText || !text || Array.from(text).length > Number(element.dataset.maxlength)) return null;
              return { id: text, text: text };
            }
          });

          $element.on('select2:open', function () {
            var search = $element.data('select2').dropdown.$search;
            state.pendingText = null;
            if (freeText) {
              search.off('input.uspPerson').on('input.uspPerson', function () {
                state.pendingText = this.value.trim();
              });
            }
            search.trigger('focus');
          }).on('change select2:select select2:clear', function () {
            state.pendingText = null;
          }).on('select2:close', function () {
            // Uma seleção explícita termina antes de confirmar o texto digitado.
            if (freeText) window.setTimeout(commitText, 0);
          });

          var initialValue = element.value;
          if (initialValue && element.dataset.resolveInitialLabel === '1') {
            window.jQuery.ajax({
              url: element.dataset.searchUrl,
              dataType: 'json',
              data: requestData(initialValue)
            }).done(function (data) {
              var person = (data.results || []).find(function (candidate) {
                return String(candidate.id) === initialValue;
              });
              if (person && element.value === initialValue && state.pendingText === null) {
                setValue(element, person.id, person.text);
              }
            });
          }
          $element.trigger('change');
        }

        if (element.form && freeText) {
          element.form.addEventListener('submit', function () {
            if (element.disabled) return;
            commitText();
            var select = $element.data('select2');
            if (select && select.isOpen()) $element.select2('close');
          });
        }

        if ($modal.length) {
          $modal.on('shown.bs.modal', function () {
            initialize();
            if (element.dataset.autoOpen === '1' && $element.data('select2')) $element.select2('open');
          });
          if ($modal.hasClass('show')) initialize();
        } else {
          initialize();
        }
      }

      function bindFields() {
        document.querySelectorAll('[data-usp-person-select]').forEach(bindField);
      }

      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindFields);
      else bindFields();
    })();
  </script>
@endPushOnce
