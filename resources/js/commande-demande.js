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
    return new Intl.NumberFormat('fr-FR').format(montant).replace(/ | /g, ' ') + ' F';
}

/**
 * Composant Alpine du formulaire de demande V2 (§4). Court, textuel, sans
 * aucune image : la vente s'est faite sur WhatsApp, le site enregistre
 * proprement ce qui a été convenu. Répéteur de souhaits + récapitulatif
 * PUREMENT INDICATIF (le mot « environ » et la mention « montant définitif
 * confirmé par la gérante » ne sont pas décoratifs).
 */
export default function commandeDemande(config) {
    return {
        articles: config.articles || [],
        communes: config.communes || {},
        tailles: config.tailles || [],
        couleurs: config.couleurs || [],
        urlReconnaissance: config.urlReconnaissance,

        nom: config.nom || '',
        telephone: config.telephone || '',
        email: config.email || '',
        commune: config.commune || '',
        quartier: config.quartier || '',
        modeLivraison: config.modeLivraison || '',
        dateSouhaitee: config.dateSouhaitee || '',
        heureSouhaitee: config.heureSouhaitee || '',
        precisions: config.precisions || '',

        lignes: [],

        clientConnu: false,
        clientMessage: '',
        envoi: false,

        init() {
            const anciens = config.souhaits;
            this.lignes = Array.isArray(anciens) && anciens.length
                ? anciens.map((s) => ({
                    article_id: String(s.article_id ?? ''),
                    taille: s.taille ?? '',
                    couleur: s.couleur ?? '',
                    quantite: Number(s.quantite ?? 1),
                }))
                : [this.ligneVide()];
        },

        ligneVide() {
            return { article_id: '', taille: '', couleur: '', quantite: 1 };
        },

        ajouterLigne() {
            this.lignes.push(this.ligneVide());
        },

        retirerLigne(index) {
            this.lignes.splice(index, 1);
            if (this.lignes.length === 0) {
                this.lignes.push(this.ligneVide());
            }
        },

        articleDe(ligne) {
            return this.articles.find((a) => String(a.id) === String(ligne.article_id)) || null;
        },

        gereTailles(ligne) {
            return this.articleDe(ligne)?.gere_tailles ?? false;
        },

        gereCouleurs(ligne) {
            return this.articleDe(ligne)?.gere_couleurs ?? false;
        },

        get articlesParCollection() {
            const groupes = {};
            for (const article of this.articles) {
                (groupes[article.collection] ||= []).push(article);
            }
            return groupes;
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

        get sousTotalEstime() {
            return this.lignes.reduce((total, ligne) => {
                const article = this.articleDe(ligne);
                return total + (article ? article.prix * (Number(ligne.quantite) || 0) : 0);
            }, 0);
        },

        get estimationTotale() {
            return this.sousTotalEstime + (this.tarifCommune || 0);
        },

        get afficherRecap() {
            return this.sousTotalEstime > 0;
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
