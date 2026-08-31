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
    return new Intl.NumberFormat('fr-FR').format(montant).replace(/ | /g, ' ') + ' francs CFA';
}

/**
 * Composant Alpine du formulaire de commande : tarif de livraison instantané,
 * champs Yango conditionnels, reconnaissance client en direct.
 */
export default function commandeForm(config) {
    return {
        communes: config.communes,
        urlReconnaissance: config.urlReconnaissance,

        nom: config.nom || '',
        telephone: config.telephone || '',
        email: config.email || '',
        commune: config.commune || '',
        modeLivraison: config.modeLivraison || '',
        dateSouhaitee: config.dateSouhaitee || '',
        heureSouhaitee: config.heureSouhaitee || '',

        clientConnu: false,
        clientMessage: '',
        envoi: false,
        rechercheEnCours: false,

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

                    const rang = ordinal(donnees.prochaine_commande_numero);
                    this.clientMessage = donnees.avantage_debloque
                        ? `Bon retour, ${donnees.nom} — ce sera votre ${rang} commande. Elle vous débloquera −${donnees.avantage_debloque} % sur la suivante.`
                        : `Bon retour, ${donnees.nom} — ce sera votre ${rang} commande.`;

                    this.nom = donnees.nom || this.nom;
                    if (donnees.email) {
                        this.email = donnees.email;
                    }
                } else {
                    this.clientConnu = false;
                    this.clientMessage = '';
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
