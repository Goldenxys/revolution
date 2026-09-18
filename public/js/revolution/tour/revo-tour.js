/**
 * Visite guidée de l'Espace RÉVOLUTION — sans dépendance externe.
 *
 * Met en surbrillance un élément réel de l'écran (via un halo positionné en
 * `position:fixed`) et affiche une bulle Précédent/Suivant/Passer à côté.
 * Se relance à tout moment depuis le bouton « Revoir le guide » de chaque
 * écran (App\Filament\Support\GuideAction). Sur le tableau de bord, se
 * lance automatiquement une seule fois (mémorisé dans localStorage).
 *
 * Une étape dont le sélecteur ne correspond à rien sur l'écran courant est
 * simplement sautée — jamais d'erreur bloquante.
 */
(function () {
    if (window.RevoTour) return;

    var STEPS = {
        dashboard: [
            {
                selector: '[data-tour="demandes-banner"]',
                titre: 'Demandes à valider',
                texte: "Le premier réflexe du matin : le nombre de demandes déposées par vos clientes et pas encore traitées. Cliquez dessus pour les ouvrir.",
            },
            {
                selector: '[data-tour="cartes-jour"]',
                titre: 'Vos chiffres du jour',
                texte: "Chiffre d'affaires, ventes validées et nouvelles clientes — uniquement ce qui est validé, jamais les frais de livraison ni les demandes en attente.",
            },
            {
                selector: '[data-tour="mois"]',
                titre: 'Le cumul du mois',
                texte: 'Pour suivre votre progression sans attendre la fin du mois.',
            },
            {
                selector: '[data-tour="table-ventes"]',
                titre: 'Les ventes du jour',
                texte: "La liste des commandes validées aujourd'hui. Cliquez sur l'œil d'une ligne pour ouvrir son détail, télécharger le reçu ou l'envoyer par WhatsApp.",
            },
            {
                selector: '[data-tour="widget-stock"]',
                titre: 'Stock à surveiller',
                texte: "S'il y a du stock faible ou une rupture, la liste apparaît ici — un raccourci vers « Mon stock » pour réagir vite.",
            },
        ],
        demandes: [
            {
                selector: '.fi-header',
                titre: 'Demandes à valider',
                texte: "Chaque demande déposée par une cliente atterrit ici, la plus ancienne en premier, tant qu'elle n'est pas composée.",
            },
            {
                selector: '.fi-ta-ctn',
                titre: "Ce que la cliente a dit",
                texte: "Un coup d'œil suffit : My Verse et son verset, ou un autre article — avec un badge « nouvelle » ou « fidèle ».",
            },
            {
                selector: '.fi-ta-actions',
                titre: 'Composer et valider',
                texte: "Ce bouton ouvre le compositeur : c'est là que vous choisissez le vrai article, la taille, la couleur et le prix, puis validez.",
            },
        ],
        composer: [
            {
                selector: '[data-tour="souhaits"]',
                titre: 'Ce que la cliente a dit',
                texte: 'À gauche, en lecture seule : ses coordonnées, son verset ou ses précisions, et sa livraison. Rien ne se modifie ici.',
            },
            {
                selector: '.fi-fo-repeater',
                titre: 'Ce que vous décidez',
                texte: "À droite, composez les vraies lignes de la commande : article, taille, couleur, quantité. Le prix est pré-rempli depuis le catalogue et reste modifiable.",
            },
            {
                selector: '[data-tour="montants"]',
                titre: 'Les montants',
                texte: "Le chiffre d'affaires et le montant à encaisser se recalculent en direct — la livraison reste toujours affichée à part.",
            },
            {
                selector: '[data-tour="valider-btn"]',
                titre: 'Valider la commande',
                texte: "Dernière étape : ce bouton comptabilise la vente, décrémente le stock et fait passer la cliente en fidèle. Une confirmation vous est demandée avant.",
            },
        ],
        stock: [
            {
                selector: '.fi-header',
                titre: 'Mon stock',
                texte: "Une ligne par variante d'article (taille × couleur), pensée pour être consultée depuis votre téléphone.",
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Modifier le stock',
                texte: "Le nombre de pièces se modifie directement dans le tableau, sans ouvrir de fiche. Utilisez les filtres pour ne voir que le stock faible ou les ruptures.",
            },
            {
                selector: '[data-tour="entree-stock"]',
                titre: 'Entrée de stock',
                texte: 'Après une réception de marchandise, ce bouton ajoute la quantité reçue à une ou plusieurs variantes en trois clics.',
            },
        ],
        commandes: [
            {
                selector: '.fi-header',
                titre: 'Commandes',
                texte: 'Les commandes déjà validées, en livraison ou livrées — les demandes en attente vivent, elles, dans « Demandes à valider ».',
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Suivi et reçu',
                texte: "Ouvrez une commande pour changer son statut de livraison, télécharger le reçu, l'envoyer par WhatsApp ou l'annuler si besoin.",
            },
        ],

        // --- Catalogue -----------------------------------------------------
        articles: [
            {
                selector: '.fi-header',
                titre: 'Vos articles',
                texte: "Chaque article du catalogue : nom, collection, type, prix, photo. C'est ce que la gérante retrouve et choisit dans le compositeur de commande.",
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Disponibilité et actif',
                texte: "La colonne Disponibilité montre le nombre de variantes réellement en stock (ex. 8/12). Le bouton Actif détermine si l'article est proposé à la gérante au compositeur.",
            },
        ],
        collections: [
            {
                selector: '.fi-header',
                titre: 'Vos collections',
                texte: "My Verse, et toutes les autres. Une collection désactivée disparaît du choix de la gérante au compositeur, sans rien supprimer.",
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Verset et modèles',
                texte: 'La colonne Verset indique si la collection réclame un verset (comme My Verse) — ça active le champ dédié dans le compositeur.',
            },
        ],
        types: [
            {
                selector: '.fi-header',
                titre: "Types d'article",
                texte: "Tee-shirt, Pull, Tote bag… chaque type précise s'il gère des tailles et/ou des couleurs, ce qui adapte automatiquement le compositeur et le catalogue.",
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Un type par article',
                texte: "Chaque article est rattaché à un seul type. Désactivez un type inutilisé plutôt que de le supprimer : l'historique reste intact.",
            },
        ],
        tailles: [
            {
                selector: '.fi-header',
                titre: 'Tailles',
                texte: 'M, L, XL, XXL… la liste que la gérante retrouve dans le compositeur pour composer chaque ligne de commande.',
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Active',
                texte: "Depuis la V2, seule la gérante choisit la taille : ce réglage ne sert plus qu'au compositeur, plus au formulaire client.",
            },
        ],
        couleurs: [
            {
                selector: '.fi-header',
                titre: 'Couleurs',
                texte: 'La palette disponible pour vos articles, avec son code visuel.',
            },
            {
                selector: '.fi-ta-ctn',
                titre: 'Active',
                texte: "Comme pour les tailles, seule la gérante choisit la couleur depuis la V2 — ce réglage ne sert plus qu'au compositeur.",
            },
        ],
    };

    var etat = null; // { page, steps, index, els: {overlay, halo, bulle} }

    function injectStyle() {
        if (document.getElementById('revo-tour-style')) return;
        var css = [
            '.revo-tour-halo{position:fixed;pointer-events:none;border-radius:10px;',
            'box-shadow:0 0 0 4000px rgba(15,10,8,.6),0 0 0 3px #AB6715;',
            'transition:top .25s ease,left .25s ease,width .25s ease,height .25s ease;z-index:2147483000;}',
            '.revo-tour-bulle{position:fixed;z-index:2147483001;max-width:320px;background:#FFFFFF;',
            'color:#17120E;border-radius:14px;box-shadow:0 20px 45px -20px rgba(0,0,0,.45);',
            'padding:18px 20px;font-family:inherit;font-size:14px;line-height:1.5;',
            'transition:top .25s ease,left .25s ease;}',
            'html.dark .revo-tour-bulle{background:#1F2937;color:#F3F4F6;}',
            '.revo-tour-bulle h3{font-size:14px;font-weight:600;margin:0 0 6px;color:#8E3914;}',
            'html.dark .revo-tour-bulle h3{color:#E7A857;}',
            '.revo-tour-bulle p{margin:0 0 14px;}',
            '.revo-tour-footer{display:flex;align-items:center;justify-content:space-between;gap:8px;}',
            '.revo-tour-pas{font-size:12px;color:#9CA3AF;}',
            '.revo-tour-btns{display:flex;gap:6px;}',
            '.revo-tour-btn{border:0;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;}',
            '.revo-tour-btn-suivant{background:#8E3914;color:#fff;}',
            '.revo-tour-btn-suivant:hover{background:#7a3010;}',
            '.revo-tour-btn-secondaire{background:transparent;color:#6B7280;}',
            '.revo-tour-btn-secondaire:hover{color:#374151;}',
            'html.dark .revo-tour-btn-secondaire{color:#9CA3AF;}',
            '.revo-tour-close{position:absolute;top:10px;right:10px;border:0;background:transparent;',
            'color:#9CA3AF;cursor:pointer;font-size:16px;line-height:1;padding:4px;}',
        ].join('');
        var style = document.createElement('style');
        style.id = 'revo-tour-style';
        style.textContent = css;
        document.head.appendChild(style);
    }

    function cible(step) {
        try {
            return document.querySelector(step.selector);
        } catch (e) {
            return null;
        }
    }

    function positionner() {
        if (!etat) return;
        var step = etat.steps[etat.index];
        var el = cible(step);

        if (!el) {
            suivant();

            return;
        }

        el.scrollIntoView({ block: 'center', behavior: 'smooth' });

        // Laisse le smooth-scroll démarrer avant de mesurer.
        requestAnimationFrame(function () {
            var r = el.getBoundingClientRect();
            var pad = 6;
            var halo = etat.els.halo;
            halo.style.top = Math.max(0, r.top - pad) + 'px';
            halo.style.left = Math.max(0, r.left - pad) + 'px';
            halo.style.width = (r.width + pad * 2) + 'px';
            halo.style.height = (r.height + pad * 2) + 'px';

            var bulle = etat.els.bulle;
            var bulleWidth = 320;
            var top = r.bottom + 16;
            var left = Math.min(Math.max(8, r.left), window.innerWidth - bulleWidth - 16);

            if (top + 160 > window.innerHeight) {
                top = Math.max(8, r.top - 16 - 160);
            }

            bulle.style.top = top + 'px';
            bulle.style.left = left + 'px';
        });
    }

    function rendreBulle() {
        var step = etat.steps[etat.index];
        var bulle = etat.els.bulle;
        var dernier = etat.index === etat.steps.length - 1;

        bulle.innerHTML =
            '<button type="button" class="revo-tour-close" data-revo-fermer aria-label="Fermer">✕</button>' +
            '<h3>' + step.titre + '</h3>' +
            '<p>' + step.texte + '</p>' +
            '<div class="revo-tour-footer">' +
            '<span class="revo-tour-pas">' + (etat.index + 1) + ' / ' + etat.steps.length + '</span>' +
            '<div class="revo-tour-btns">' +
            (etat.index > 0 ? '<button type="button" class="revo-tour-btn revo-tour-btn-secondaire" data-revo-precedent>Précédent</button>' : '') +
            '<button type="button" class="revo-tour-btn revo-tour-btn-secondaire" data-revo-passer>Passer</button>' +
            '<button type="button" class="revo-tour-btn revo-tour-btn-suivant" data-revo-suivant>' + (dernier ? 'Terminé' : 'Suivant') + '</button>' +
            '</div></div>';
    }

    function suivant() {
        if (!etat) return;

        if (etat.index >= etat.steps.length - 1) {
            terminer();

            return;
        }

        etat.index += 1;
        rendreBulle();
        positionner();
    }

    function precedent() {
        if (!etat || etat.index === 0) return;
        etat.index -= 1;
        rendreBulle();
        positionner();
    }

    function onClavier(e) {
        if (!etat) return;
        if (e.key === 'Escape') terminer();
        if (e.key === 'ArrowRight' || e.key === 'Enter') suivant();
        if (e.key === 'ArrowLeft') precedent();
    }

    function terminer() {
        if (!etat) return;
        try {
            localStorage.setItem('revo_tour_' + etat.page + '_vu', '1');
        } catch (e) {
            // stockage indisponible (navigation privée…) : sans conséquence.
        }
        etat.els.halo.remove();
        etat.els.bulle.remove();
        window.removeEventListener('resize', positionner);
        window.removeEventListener('scroll', positionner, true);
        document.removeEventListener('keydown', onClavier);
        etat = null;
    }

    function demarrer(page) {
        var toutesLesEtapes = STEPS[page] || [];
        var etapes = toutesLesEtapes.filter(function (s) {
            return !!cible(s);
        });

        if (!etapes.length) return;

        if (etat) terminer();

        injectStyle();

        var halo = document.createElement('div');
        halo.className = 'revo-tour-halo';
        var bulle = document.createElement('div');
        bulle.className = 'revo-tour-bulle';
        document.body.appendChild(halo);
        document.body.appendChild(bulle);

        etat = { page: page, steps: etapes, index: 0, els: { halo: halo, bulle: bulle } };

        bulle.addEventListener('click', function (e) {
            if (e.target.closest('[data-revo-suivant]')) suivant();
            else if (e.target.closest('[data-revo-precedent]')) precedent();
            else if (e.target.closest('[data-revo-passer]')) terminer();
            else if (e.target.closest('[data-revo-fermer]')) terminer();
        });

        window.addEventListener('resize', positionner);
        window.addEventListener('scroll', positionner, true);
        document.addEventListener('keydown', onClavier);

        rendreBulle();
        positionner();
    }

    function autoDemarrer(page) {
        try {
            if (localStorage.getItem('revo_tour_' + page + '_vu')) return;
        } catch (e) {
            return;
        }
        setTimeout(function () {
            demarrer(page);
        }, 700);
    }

    window.RevoTour = { start: demarrer, autoStart: autoDemarrer };
})();
