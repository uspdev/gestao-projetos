@props([
    'name',
    'label' => null,
    'value' => '',
    'id' => null,
    'groupClass' => 'form-group mb-3',
    'errorBag' => null,
    'markdownProfile' => null,
])

@php
  $textareaId = $id ?? $name;
  $validationErrors = $errorBag ?? $errors;
@endphp

@if ($markdownProfile)
  @include('components.markdown.markdown-css')
@endif

<div class="{{ $groupClass }}">
  @if ($label)
    <label for="{{ $textareaId }}">{{ $label }}</label>
  @endif
  <textarea name="{{ $name }}" id="{{ $textareaId }}" data-autogrow-textarea
    @if ($markdownProfile) data-markdown-editor data-markdown-profile="{{ $markdownProfile }}"
      data-markdown-preview-url="{{ route('markdown.preview') }}" spellcheck="true" @endif
    {{ $attributes->merge(['class' => 'form-control textarea-autogrow ' . ($validationErrors->has($name) ? 'is-invalid' : ''), 'style' => 'resize: none; overflow: hidden;']) }}>{{ old($name, $value) }}</textarea>
  @if ($validationErrors->has($name))
    <div class="invalid-feedback d-block">{{ $validationErrors->first($name) }}</div>
  @endif
</div>
