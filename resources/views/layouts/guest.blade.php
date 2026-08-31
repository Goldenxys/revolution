<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Espace RÉVOLUTION — Connexion</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('favicon/apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans font-light text-encre antialiased bg-creme">
        <div class="min-h-screen flex flex-col items-center justify-center px-6 py-10">
            <a href="{{ route('accueil') }}" class="mb-8 sm:mb-10">
                <img src="{{ asset('img/logo-revolution.png') }}" alt="RÉVOLUTION — Même ta garde-robe intéresse JÉSUS !"
                     class="w-[220px] sm:w-[255px] h-auto">
            </a>

            <div class="w-full max-w-[420px]">
                <p class="text-center text-xs uppercase tracking-[0.18em] text-or font-semibold mb-6">
                    Espace RÉVOLUTION
                </p>

                <div class="bg-carte border border-filet px-6 py-8 sm:px-8 sm:py-9">
                    {{ $slot }}
                </div>

                <p class="mt-8 text-center text-[11px] tracking-wide text-texte-secondaire">
                    <a href="{{ route('accueil') }}" class="hover:text-rouille transition underline underline-offset-4 decoration-filet">
                        ← Retour au site de commande
                    </a>
                </p>
            </div>
        </div>
    </body>
</html>
