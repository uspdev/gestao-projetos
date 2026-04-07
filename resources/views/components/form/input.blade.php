@props(['name', 'label', 'type' => 'text', 'value' => ''])

<div class="form-group mb-3">
    <label for="{{ $name }}">{{ $label }}</label>
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $name }}" 
        value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : '')]) }}
    >
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>