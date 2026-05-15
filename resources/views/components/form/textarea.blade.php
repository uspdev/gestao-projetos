@props(['name', 'label', 'value' => ''])

<div class="form-group mb-3">
  <label for="{{ $name }}">{{ $label }}</label>
  <textarea name="{{ $name }}"
    id="{{ $name }}"{{ $attributes->merge(['class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : '')]) }}>{!! htmlspecialchars(old($name, $value), ENT_QUOTES, 'UTF-8') !!}</textarea>
  @error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>
