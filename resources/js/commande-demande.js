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
 * Composant Alpine du formulaire de demande V2 (§4). Court, textuel, sans
 * aucune image. Le client ne choisit plus d'article : il indique s'il veut
 * un tee-shirt My Verse (et fournit alors son verset, sa taille, sa
 * couleur) ou un autre article de la collection (la gérante composera la
 * commande depuis son panneau). Les frais de livraison sont rappelés, mais
 * aucun total ferme n'est calculé côté client.
 */
export default function commandeDemande(config) {
    return {
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

        collection: config.collection || '',
        taille: config.taille || '',
        couleur: config.couleur || '',
        versetReference: config.versetReference || '',
        versetTexte: config.versetTexte || '',

        clientConnu: false,
        clientMessage: '',
        envoi: false,

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
