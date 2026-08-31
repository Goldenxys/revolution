@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'mx-auto w-full max-w-colonne px-6 '.$class]) }}>
    {{ $slot }}
</div>
