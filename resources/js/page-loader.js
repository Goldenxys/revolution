/**
 * Barre de chargement fine en haut de l'écran, façon YouTube/NProgress.
 *
 * Le site public est en navigation plein-page classique (pas de SPA) : sur
 * de l'hébergement mutualisé, une requête peut prendre un instant à
 * répondre. Cette barre donne un retour visuel immédiat dès le clic, pour
 * que la cliente sache que quelque chose se passe — et pour décourager les
 * double-clics/doubles soumissions pendant l'attente.
 */
export default function initPageLoader() {
    if (document.getElementById('revo-page-loader')) return;

    const bar = document.createElement('div');
    bar.id = 'revo-page-loader';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);

    const start = () => {
        bar.classList.remove('is-loading');
        // Force un reflow pour redémarrer proprement l'animation si une
        // navigation précédente a été interrompue (bouton "retour" par ex.).
        void bar.offsetWidth;
        bar.classList.add('is-loading');
        document.documentElement.setAttribute('data-navigating', '');
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;

        // On ignore tout ce qui ne mène pas à une nouvelle page du site :
        // nouvel onglet, téléchargement, ancre, mailto/tel, ou domaine externe
        // (WhatsApp, etc.).
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        if (/^(mailto|tel):/.test(link.getAttribute('href'))) return;

        let url;
        try {
            url = new URL(link.href, window.location.href);
        } catch (erreur) {
            return;
        }

        if (url.origin !== window.location.origin) return;
        if (url.href.split('#')[0] === window.location.href.split('#')[0]) return;

        start();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && !form.hasAttribute('data-no-loader')) {
            start();
        }
    });

    // Le cache de navigation arrière/avant du navigateur peut restaurer une
    // page avec la barre encore visible : on la remet à zéro à chaque
    // affichage réel de la page.
    window.addEventListener('pageshow', () => {
        bar.classList.remove('is-loading');
        document.documentElement.removeAttribute('data-navigating');
    });
}
