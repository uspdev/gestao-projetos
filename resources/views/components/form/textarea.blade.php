@props(['name', 'label' => null, 'value' => '', 'id' => null, 'groupClass' => 'form-group mb-3'])

@php
  $textareaId = $id ?? $name;
@endphp

<div class="{{ $groupClass }}">
  @if ($label)
    <label for="{{ $textareaId }}">{{ $label }}</label>
  @endif
  <textarea name="{{ $name }}" id="{{ $textareaId }}" data-autogrow-textarea
    {{ $attributes->merge(['class' => 'form-control textarea-autogrow ' . ($errors->has($name) ? 'is-invalid' : ''), 'style' => 'resize: none; overflow: hidden;']) }}>{{ old($name, $value) }}</textarea>
  @error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>

