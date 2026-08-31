<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-6 bg-carte border border-filet rounded-none font-medium text-xs text-encre uppercase tracking-widest hover:border-rouille focus:outline-none focus:ring-2 focus:ring-rouille focus:ring-offset-2 disabled:opacity-40 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
