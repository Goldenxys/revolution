import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // L'espace gérante (Filament) a son propre thème/bundle CSS : pas
        // besoin de scanner ses vues pour le bundle public.
        '!./resources/views/filament/**',
    ],

    theme: {
        extend: {
            colors: {
                rouille: '#8E3914',
                or: '#AB6715',
                creme: '#FBF8F4',
                carte: '#FFFFFF',
                encre: '#17120E',
                'texte-secondaire': '#7A6E63',
                filet: '#E9E0D5',
            },
            fontFamily: {
                // Une seule famille, simple et lisible façon e-commerce —
                // plus de serif décoratif pour les titres.
                sans: ['Poppins', 'system-ui', 'sans-serif'],
                serif: ['Poppins', 'system-ui', 'sans-serif'],
            },
            borderRadius: {
                DEFAULT: '2px',
                none: '0px',
                sm: '2px',
                md: '2px',
                lg: '2px',
                xl: '2px',
                full: '9999px',
            },
            maxWidth: {
                colonne: '640px',
            },
        },
    },

    plugins: [forms],
};
