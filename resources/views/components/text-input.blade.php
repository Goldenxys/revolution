@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full border border-filet bg-carte px-4 py-3 text-base text-encre focus:border-rouille focus:ring-0 rounded-none']) }}>
