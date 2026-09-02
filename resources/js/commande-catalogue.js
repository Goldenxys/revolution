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
    return new Intl.NumberFormat('fr-FR').format(montant).replace(/ | /g, ' ') + ' francs CFA';
}

/**
 * Composant Alpine du nouveau parcours de commande (catalogue) : cascade
 * collection → article → taille → couleur → quantité, récapitulatif de
 * prix instantané, reconnaissance client en direct. Le catalogue complet
 * est injecté au montage (voir CommandeCatalogueController::creer()) — pas
 * de fetch au chargement, pour éviter tout flash de contenu vide.
 *
 * Le prix affiché ici est purement indicatif : le serveur recalcule tout
 * depuis la base à l'enregistrement (StoreCommandeCatalogueRequest +
 * CommandeCatalogueController::store()), qui revérifie aussi la
 * disponibilité de la combinaison choisie au moment de la validation.
 */
export default function commandeCatalogue(config) {
    return {
        collections: config.collections,
        articles: config.articles,
        tailles: config.tailles,
        couleurs: config.couleurs,
        communes: config.communes,
        urlReconnaissance: config.urlReconnaissance,

        slugCollectionPreselectionnee: config.slugCollectionPreselectionnee || null,
        collectionId: null,
        articleId: null,
        tailleId: null,
        couleurId: null,
        quantite: 1,
        verset: '',
        modele: '',
        messageCouleurIndisponible: '',

        nom: '',
        telephone: '',
        email: '',
        clientConnu: false,
        clientMessage: '',
        avantagePourcentage: 0,
        rechercheEnCours: false,

        commune: '',
        modeLivraison: '',
        dateSouhaitee: '',
        heureSouhaitee: '',

        envoi: false,
        recapOuvert: false,

        init() {
            if (this.slugCollectionPreselectionnee) {
                const trouvee = this.collections.find((c) => c.slug === this.slugCollectionPreselectionnee);
                if (trouvee) {
                    this.collectionId = trouvee.id;
                }
            }

            // Une seule collection : on saute l'étape de choix.
            if (!this.collectionId && this.collections.length === 1) {
                this.collectionId = this.collections[0].id;
            }
        },

        get collectionSelectionnee() {
            return this.collections.find((c) => c.id === this.collectionId) || null;
        },

        get articlesDeLaCollection() {
            return this.articles.filter((a) => a.collection_id === this.collectionId);
        },

        get articleSelectionne() {
            return this.articles.find((a) => a.id === this.articleId) || null;
        },

        get tailleSelectionnee() {
            return this.tailles.find((t) => t.id === this.tailleId) || null;
        },

        get couleurSelectionnee() {
            return this.couleurs.find((c) => c.id === this.couleurId) || null;
        },

        get afficherVerset() {
            return !!this.collectionSelectionnee?.verset_requis;
        },

        get modelesDisponibles() {
            return this.collectionSelectionnee?.modeles_disponibles || [];
        },

        varianteExiste(tailleId, couleurId) {
            const article = this.articleSelectionne;
            if (!article) return false;

            return article.variantes.some((v) => {
                const memeTaille = article.gere_tailles ? v.taille_id === tailleId : true;
                const memeCouleur = article.gere_couleurs ? v.couleur_id === couleurId : true;
                return memeTaille && memeCouleur;
            });
        },

        get taillesDisponiblesPourArticle() {
            const article = this.articleSelectionne;
            if (!article || !article.gere_tailles) return [];

            const idsDisponibles = new Set(article.variantes.map((v) => v.taille_id));
            return this.tailles.filter((t) => idsDisponibles.has(t.id));
        },

        get couleursDisponiblesPourArticle() {
            const article = this.articleSelectionne;
            if (!article || !article.gere_couleurs) return [];

            const filtreParTaille = article.gere_tailles && this.tailleId !== null;

            const idsDisponibles = new Set(
                article.variantes
                    .filter((v) => !filtreParTaille || v.taille_id === this.tailleId)
                    .map((v) => v.couleur_id)
            );

            return this.couleurs.filter((c) => idsDisponibles.has(c.id));
        },

        choisirCollection(id) {
            this.collectionId = id;
            this.articleId = null;
            this.tailleId = null;
            this.couleurId = null;
        },

        choisirArticle(id) {
            this.articleId = id;
            this.tailleId = null;
            this.couleurId = null;
            this.quantite = 1;
            this.messageCouleurIndisponible = '';
        },

        choisirTaille(id) {
            this.tailleId = id;

            if (this.couleurId !== null && !this.varianteExiste(id, this.couleurId)) {
                const libelle = this.tailles.find((t) => t.id === id)?.libelle || '';
                this.messageCouleurIndisponible = `Cette couleur n'est pas disponible en ${libelle}.`;
                this.couleurId = null;
            } else {
                this.messageCouleurIndisponible = '';
            }
        },

        get tarifCommune() {
            return this.commune && this.communes[this.commune] !== undefined
                ? this.communes[this.commune]
                : null;
        },

        get tarifFormate() {
            return this.tarifCommune !== null ? formatFrancs(this.tarifCommune) : '';
        },

        get dateLongue() {
            return formatDateLongue(this.dateSouhaitee);
        },

        get estYango() {
            return this.modeLivraison === 'yango';
        },

        get estLivreur() {
            return this.modeLivraison === 'livreur';
        },

        get sousTotal() {
            return this.articleSelectionne ? this.articleSelectionne.prix * this.quantite : 0;
        },

        get fraisLivraison() {
            return this.tarifCommune || 0;
        },

        get remiseMontant() {
            if (!this.avantagePourcentage || !this.articleSelectionne) return 0;
            return Math.round(((this.sousTotal + this.fraisLivraison) * this.avantagePourcentage) / 100);
        },

        get total() {
            return this.sousTotal + this.fraisLivraison - this.remiseMontant;
        },

        get totalFormate() {
            return formatFrancs(this.total);
        },

        formatPrix(montant) {
            return formatFrancs(montant);
        },

        get peutValider() {
            const article = this.articleSelectionne;
            if (!article) return false;
            if (article.gere_tailles && !this.tailleId) return false;
            if (article.gere_couleurs && !this.couleurId) return false;
            if (!this.nom.trim() || !this.telephone.trim()) return false;
            if (!this.commune || !this.modeLivraison) return false;
            if (this.estYango && (!this.dateSouhaitee || !this.heureSouhaitee)) return false;
            return true;
        },

        async verifierClient() {
            if (!this.telephone.trim() && !this.nom.trim()) {
                return;
            }

            this.rechercheEnCours = true;

            const params = new URLSearchParams({ telephone: this.telephone, nom: this.nom });

            try {
                const reponse = await fetch(`${this.urlReconnaissance}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                const donnees = await reponse.json();

                if (donnees.connu) {
                    this.clientConnu = true;
                    this.avantagePourcentage = donnees.avantage_debloque || 0;

                    const rang = ordinal(donnees.prochaine_commande_numero);
                    this.clientMessage = donnees.avantage_debloque
                        ? `Bon retour, ${donnees.nom} — ce sera votre ${rang} commande. Elle vous débloquera −${donnees.avantage_debloque} % sur cette commande.`
                        : `Bon retour, ${donnees.nom} — ce sera votre ${rang} commande.`;

                    this.nom = donnees.nom || this.nom;
                    if (donnees.email) {
                        this.email = donnees.email;
                    }
                } else {
                    this.clientConnu = false;
                    this.clientMessage = '';
                    this.avantagePourcentage = 0;
                }
            } catch (erreur) {
                // Silencieux : la reconnaissance est un confort, pas un blocant.
                this.clientConnu = false;
            } finally {
                this.rechercheEnCours = false;
            }
        },
    };
}
