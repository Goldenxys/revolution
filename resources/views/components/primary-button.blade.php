<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-6 bg-rouille border border-transparent rounded-none font-medium text-xs text-white uppercase tracking-widest hover:bg-rouille/90 focus:outline-none focus:ring-2 focus:ring-rouille focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
