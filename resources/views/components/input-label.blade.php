@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm text-encre mb-2']) }}>
    {{ $value ?? $slot }}
</label>
