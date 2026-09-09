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
 * Composant Alpine du formulaire de demande V2 (§4). Deux parcours :
 *   • My Verse — répéteur de versets (un tee-shirt = un verset), la
 *     cliente ne choisit ni taille ni couleur (la gérante s'en charge).
 *   • Autre collection — seulement coordonnées, précisions et livraison.
 * Les frais de livraison sont rappelés, aucun total ferme n'est calculé.
 */
export default function commandeDemande(config) {
    return {
        type: config.type || 'my_verse',
        communes: config.communes || {},
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

        versets: [],

        clientConnu: false,
        clientMessage: '',
        envoi: false,

        init() {
            const anciens = config.versets;
            this.versets = Array.isArray(anciens) && anciens.length
                ? anciens.map((v) => ({ reference: v.reference ?? '', texte: v.texte ?? '' }))
                : [this.versetVide()];
        },

        versetVide() {
            return { reference: '', texte: '' };
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
