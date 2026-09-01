<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#8E3914">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titre ?? 'RÉVOLUTION — Prise de commande' }}</title>
    <meta name="description" content="Même ta garde-robe intéresse JÉSUS ! Passez votre commande RÉVOLUTION en moins de deux minutes.">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans font-light antialiased bg-creme text-encre min-h-screen">
    <div class="min-h-screen flex flex-col">
        {{-- Logo : centré, en haut de chaque écran. Largeur fixée via un
             conteneur mx-auto (et non un pourcentage sur l'image elle-même,
             peu fiable dans un contexte flex) pour un centrage stable sur
             tous les mobiles. --}}
        <div class="px-6 pt-10 pb-8 sm:pt-14 sm:pb-10">
            <a href="{{ route('accueil') }}" class="block mx-auto w-full max-w-[240px] sm:max-w-[255px]">
                <img src="{{ asset('img/logo-revolution.png') }}" alt="RÉVOLUTION — Même ta garde-robe intéresse JÉSUS !"
                     class="block w-full h-auto">
            </a>
        </div>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <div class="mx-auto w-full max-w-colonne px-6">
            <div class="pt-10 pb-6 text-center text-[11px] tracking-wide text-texte-secondaire">
                RÉVOLUTION — Abidjan, Côte d’Ivoire
            </div>
            <div class="pb-10 flex items-center justify-center gap-4 text-[11px] text-texte-secondaire">
                <a href="{{ route('legal.mentions-legales') }}" class="hover:text-rouille transition underline underline-offset-4 decoration-filet">
                    Mentions légales
                </a>
                <span class="text-filet">·</span>
                <a href="{{ route('legal.confidentialite') }}" class="hover:text-rouille transition underline underline-offset-4 decoration-filet">
                    Confidentialité
                </a>
            </div>
        </div>
    </div>
</body>
</html>
