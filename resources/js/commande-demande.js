function ordinal(nombre) {
    return nombre === 1 ? '1ʳᵉ' : `${nombre}ᵉ`;
}

function formatDateLongue(iso) {
    if (!iso) return '';
    const date = new Date(`${iso}T00:00:00`);
    if (Number.isNaN(date.getTime())) return '';

    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function formatFrancs(montant) {
    return new Intl.NumberFormat('fr-FR').format(montant).replace(/ | /g, ' ') + ' F';
}

/**
 * Normalise pour une comparaison insensible à la casse et aux accents —
 * même esprit que Client::nomNormalise() côté serveur, pour que la
 * recherche d'article trouve « t-shirt » en tapant « tshirt » ou « Ecriture »
 * en tapant « écriture ».
 */
function normaliser(texte) {
    return (texte || '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();
}

/**
 * Composant Alpine du formulaire de demande V2 (§4). Deux parcours :
 *   • My Verse — répéteur de versets (un tee-shirt = un verset), avec une
 *     taille/couleur souhaitées par verset (référentiels globaux, sans
 *     lien au stock : My Verse est fabriqué à la demande — c'est le modèle
 *     exact que la gérante choisit).
 *   • Autre collection — répéteur d'articles, nom assisté par recherche sur
 *     le catalogue (chargé une fois, filtré ici) ; une fois un article du
 *     catalogue choisi, taille/couleur se limitent à ses variantes
 *     réellement achetables (le catalogue ne renvoie déjà que celles-ci —
 *     voir CatalogueController::payload()). Un nom non reconnu reste
 *     possible (article hors catalogue) avec des select taille/couleur
 *     ouverts sur les référentiels globaux.
 * Dans tous les cas : aucun total ferme n'est calculé côté client, c'est un
 * souhait que la gérante reprend et confirme.
 */
export default function commandeDemande(config) {
    return {
        type: config.type || 'my_verse',
        communes: config.communes || {},
        tailles: config.tailles || [],
        couleurs: config.couleurs || [],
        urlReconnaissance: config.urlReconnaissance,
        urlCatalogue: config.urlCatalogue,

        nom: config.nom || '',
        telephone: config.telephone || '',
        email: config.email || '',
        commune: config.commune || '',
        quartier: config.quartier || '',
        modeLivraison: config.modeLivraison || '',
        dateSouhaitee: config.dateSouhaitee || '',
        heureSouhaitee: config.heureSouhaitee || '',
        precisions: config.precisions || '',

        versets: [],
        articles: [],
        catalogueArticles: [],

        clientConnu: false,
        clientMessage: '',
        envoi: false,

        init() {
            const anciensVersets = config.versets;
            this.versets = Array.isArray(anciensVersets) && anciensVersets.length
                ? anciensVersets.map((v) => ({
                    reference: v.reference ?? '',
                    texte: v.texte ?? '',
                    taille_id: v.taille_id ?? '',
                    couleur_id: v.couleur_id ?? '',
                }))
                : [this.versetVide()];

            const anciensArticles = config.articles;
            this.articles = Array.isArray(anciensArticles) && anciensArticles.length
                ? anciensArticles.map((a) => ({
                    nom: a.nom ?? '',
                    article_id: a.article_id ?? '',
                    taille_id: a.taille_id ?? '',
                    couleur_id: a.couleur_id ?? '',
                    quantite: a.quantite ?? 1,
                    rechercheOuverte: false,
                }))
                : [this.articleVide()];

            if (this.type === 'autre' && this.urlCatalogue) {
                this.chargerCatalogue();
            }
        },

        async chargerCatalogue() {
            try {
                const reponse = await fetch(this.urlCatalogue, { headers: { Accept: 'application/json' } });
                const donnees = await reponse.json();
                this.catalogueArticles = donnees.articles || [];
            } catch (erreur) {
                this.catalogueArticles = [];
            }
        },

        versetVide() {
            return { reference: '', texte: '', taille_id: '', couleur_id: '' };
        },

        ajouterVerset() {
            this.versets.push(this.versetVide());
        },

        retirerVerset(index) {
            this.versets.splice(index, 1);
            if (this.versets.length === 0) {
                this.versets.push(this.versetVide());
            }
        },

        articleVide() {
            return { nom: '', article_id: '', taille_id: '', couleur_id: '', quantite: 1, rechercheOuverte: false };
        },

        ajouterArticle() {
            this.articles.push(this.articleVide());
        },

        retirerArticle(index) {
            this.articles.splice(index, 1);
            if (this.articles.length === 0) {
                this.articles.push(this.articleVide());
            }
        },

        /**
         * Suggestions du catalogue pour ce qui est tapé — jusqu'à 8,
         * uniquement à partir de 2 caractères pour éviter d'afficher tout
         * le catalogue au premier clic dans un champ vide.
         */
        suggestionsPour(nom) {
            const recherche = normaliser(nom);
            if (recherche.length < 2) {
                return [];
            }

            return this.catalogueArticles
                .filter((article) => normaliser(article.nom).includes(recherche))
                .slice(0, 8);
        },

        choisirArticle(index, suggestion) {
            const article = this.articles[index];
            article.nom = suggestion.nom;
            article.article_id = suggestion.id;
            article.taille_id = '';
            article.couleur_id = '';
            article.rechercheOuverte = false;
        },

        /**
         * Si la cliente modifie le texte après avoir choisi une suggestion,
         * le lien vers cet article précis ne tient plus — mieux vaut repartir
         * d'un nom libre (hors catalogue) que de garder un article_id qui ne
         * correspond plus à ce qui est écrit.
         */
        rechercherArticle(index) {
            const article = this.articles[index];
            const trouve = this.catalogueArticles.find((a) => a.id === article.article_id);

            if (!trouve || normaliser(trouve.nom) !== normaliser(article.nom)) {
                article.article_id = '';
            }

            article.rechercheOuverte = true;
        },

        // Laisse le temps au @mousedown.prevent d'une suggestion de
        // s'exécuter avant que le blur ne referme la liste.
        fermerRechercheDifferee(index) {
            setTimeout(() => {
                if (this.articles[index]) {
                    this.articles[index].rechercheOuverte = false;
                }
            }, 150);
        },

        /**
         * Tailles proposées pour une ligne d'article : celles, réellement
         * achetables, de l'article du catalogue choisi (le catalogue ne
         * renvoie déjà que ses variantes disponibles) ; sinon, le
         * référentiel global ouvert (article hors catalogue).
         */
        optionsTailles(article) {
            const catalogueArticle = this.catalogueArticles.find((a) => a.id === article.article_id);

            if (catalogueArticle) {
                if (!catalogueArticle.gere_tailles) {
                    return [];
                }

                const idsTailles = new Set(catalogueArticle.variantes.map((v) => v.taille_id).filter(Boolean));

                return this.tailles.filter((t) => idsTailles.has(t.id));
            }

            return this.tailles;
        },

        optionsCouleurs(article) {
            const catalogueArticle = this.catalogueArticles.find((a) => a.id === article.article_id);

            if (catalogueArticle) {
                if (!catalogueArticle.gere_couleurs) {
                    return [];
                }

                const idsCouleurs = new Set(catalogueArticle.variantes.map((v) => v.couleur_id).filter(Boolean));

                return this.couleurs.filter((c) => idsCouleurs.has(c.id));
            }

            return this.couleurs;
        },

        get tarifCommune() {
            return this.commune && this.communes[this.commune] !== undefined
                ? this.communes[this.commune]
                : null;
        },

        get estYango() {
            return this.modeLivraison === 'yango';
        },

        get dateLongue() {
            return formatDateLongue(this.dateSouhaitee);
        },

        francs(montant) {
            return formatFrancs(montant);
        },

        async verifierClient() {
            if (!this.telephone.trim() && !this.nom.trim()) {
                return;
            }

            const params = new URLSearchParams({ telephone: this.telephone, nom: this.nom });

            try {
                const reponse = await fetch(`${this.urlReconnaissance}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                const donnees = await reponse.json();

                if (donnees.connu) {
                    this.clientConnu = true;
                    const rang = ordinal(donnees.prochaine_commande_numero);
                    this.clientMessage = donnees.avantage_debloque
                        ? `Bon retour, ${donnees.nom}. Ce sera votre ${rang} commande — remise fidélité applicable.`
                        : `Bon retour, ${donnees.nom}. Ce sera votre ${rang} commande.`;
                    this.nom = donnees.nom || this.nom;
                    if (donnees.email) {
                        this.email = donnees.email;
                    }
                } else {
                    this.clientConnu = false;
                    this.clientMessage = '';
                }
            } catch (erreur) {
                this.clientConnu = false;
            }
        },
    };
}
