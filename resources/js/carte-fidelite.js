const COULEURS = {
    rouille: '#8E3914',
    or: '#AB6715',
    encre: '#17120E',
    texteSecondaire: '#7A6E63',
    filet: '#E9E0D5',
    blanc: '#FFFFFF',
    grisPastille: '#C9BEB0',
};

const LARGEUR = 1080;
const HAUTEUR = 1560;

function slugify(texte) {
    return (texte || 'client')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'client';
}

function envelopperTexte(ctx, texte, xCentre, y, largeurMax, interligne) {
    const mots = texte.split(' ');
    let ligne = '';
    let yCourant = y;

    for (const mot of mots) {
        const essai = ligne ? `${ligne} ${mot}` : mot;
        if (ctx.measureText(essai).width > largeurMax && ligne) {
            ctx.fillText(ligne, xCentre, yCourant);
            ligne = mot;
            yCourant += interligne;
        } else {
            ligne = essai;
        }
    }
    if (ligne) {
        ctx.fillText(ligne, xCentre, yCourant);
        yCourant += interligne;
    }

    return yCourant;
}

function chargerImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = src;
    });
}

async function chargerPolices() {
    const polices = [
        '300 22px "Poppins"',
        '400 22px "Poppins"',
        '500 22px "Poppins"',
        '600 22px "Poppins"',
        '700 22px "Poppins"',
    ];
    try {
        await Promise.all(polices.map((police) => document.fonts.load(police)));
        await document.fonts.ready;
    } catch (erreur) {
        // Si les polices ne se chargent pas, le canvas retombera sur les
        // polices système : le téléchargement reste possible.
    }
}

async function dessinerCarte(canvas, config) {
    const ctx = canvas.getContext('2d');
    canvas.width = LARGEUR;
    canvas.height = HAUTEUR;

    await chargerPolices();

    // Fond
    ctx.fillStyle = COULEURS.blanc;
    ctx.fillRect(0, 0, LARGEUR, HAUTEUR);

    // Cadre fin
    ctx.strokeStyle = COULEURS.filet;
    ctx.lineWidth = 3;
    ctx.strokeRect(24, 24, LARGEUR - 48, HAUTEUR - 48);

    let y = 130;

    // Logo
    try {
        const logo = await chargerImage(config.logoUrl);
        const largeurLogo = 380;
        const hauteurLogo = (logo.height / logo.width) * largeurLogo;
        ctx.drawImage(logo, (LARGEUR - largeurLogo) / 2, y - hauteurLogo, largeurLogo, hauteurLogo);
        y += 40;
    } catch (erreur) {
        // Pas de logo disponible : on continue sans.
    }

    // Étiquette or
    ctx.fillStyle = COULEURS.or;
    ctx.font = '600 26px Poppins, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.save();
    ctx.font = '500 26px Poppins, sans-serif';
    ctx.fillText('C A R T E   D E   F I D É L I T É   R E V O .', LARGEUR / 2, y + 40);
    ctx.restore();
    y += 100;

    // Nom du client
    ctx.fillStyle = COULEURS.encre;
    ctx.font = '700 54px Poppins, sans-serif';
    ctx.fillText(config.nom.toUpperCase(), LARGEUR / 2, y + 30);
    y += 90;

    // Paragraphe de bienvenue
    ctx.fillStyle = COULEURS.texteSecondaire;
    ctx.font = '300 26px Poppins, sans-serif';
    y = envelopperTexte(
        ctx,
        'Vous faites partie de nos précieux clients RÉVOLUTION. Merci pour votre confiance et pour chacune de vos commandes.',
        LARGEUR / 2,
        y + 20,
        780,
        38
    );
    y += 60;

    // Pastilles 2 · 4 · 6 · 8
    const seuils = Object.keys(config.paliers).map(Number);
    const rayon = 78;
    const marge = 130;
    const espace = (LARGEUR - marge * 2) / (seuils.length - 1);

    seuils.forEach((seuil, index) => {
        const cx = marge + espace * index;
        const cy = y + rayon;
        const atteint = config.palier >= seuil;

        ctx.beginPath();
        ctx.arc(cx, cy, rayon, 0, Math.PI * 2);
        if (atteint) {
            ctx.fillStyle = COULEURS.rouille;
            ctx.fill();
        } else {
            ctx.lineWidth = 3;
            ctx.strokeStyle = COULEURS.grisPastille;
            ctx.stroke();
        }

        ctx.fillStyle = atteint ? COULEURS.blanc : COULEURS.texteSecondaire;
        ctx.font = '700 40px Poppins, sans-serif';
        ctx.fillText(String(seuil), cx, cy + 14);

        // Coche noire sous les pastilles atteintes
        let yLabel = cy + rayon + 34;
        if (atteint) {
            ctx.strokeStyle = COULEURS.encre;
            ctx.lineWidth = 6;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.beginPath();
            ctx.moveTo(cx - 16, cy + rayon + 22);
            ctx.lineTo(cx - 4, cy + rayon + 34);
            ctx.lineTo(cx + 18, cy + rayon + 10);
            ctx.stroke();
            yLabel = cy + rayon + 56;
        }

        // Pourcentage sous chaque pastille
        ctx.fillStyle = COULEURS.encre;
        ctx.font = '500 30px Poppins, sans-serif';
        ctx.fillText(`−${config.paliers[seuil]} %`, cx, yLabel + 26);
    });

    y = y + rayon * 2 + 130;

    // Ligne d'état
    ctx.fillStyle = COULEURS.encre;
    ctx.font = '500 30px Poppins, sans-serif';
    ctx.fillText(`${config.nbCommandes} commandes enregistrées · palier ${config.palier}/8`, LARGEUR / 2, y);
    y += 60;

    // Message d'avantage
    ctx.fillStyle = COULEURS.rouille;
    ctx.font = '400 26px Poppins, sans-serif';
    const messageAvantage = config.avantage
        ? `Vous venez de débloquer −${config.avantage} % sur votre prochaine commande RÉVOLUTION. Il vous suffira de nous envoyer une capture de votre carte de fidélité au moment de votre prochaine commande.`
        : `Encore ${config.commandesRestantes} commande${config.commandesRestantes > 1 ? 's' : ''} et vous débloquez −${config.prochainAvantage} % sur votre commande suivante.`;
    y = envelopperTexte(ctx, messageAvantage, LARGEUR / 2, y, 820, 38);
    y += 50;

    // Rappel des paliers
    ctx.strokeStyle = COULEURS.filet;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(140, y);
    ctx.lineTo(LARGEUR - 140, y);
    ctx.stroke();
    y += 46;

    ctx.fillStyle = COULEURS.texteSecondaire;
    ctx.font = '400 24px Poppins, sans-serif';
    const rappel = seuils.map((seuil) => `${seuil}ᵉ cde → −${config.paliers[seuil]} %`).join('    ·    ');
    ctx.fillText(rappel, LARGEUR / 2, y);
}

export default function carteFidelite(config) {
    return {
        telechargementEnCours: false,

        async telecharger() {
            this.telechargementEnCours = true;
            try {
                const canvas = document.createElement('canvas');
                await dessinerCarte(canvas, config);

                const lien = document.createElement('a');
                lien.download = `carte-fidelite-revolution-${slugify(config.nom)}.png`;
                lien.href = canvas.toDataURL('image/png');
                document.body.appendChild(lien);
                lien.click();
                document.body.removeChild(lien);
            } finally {
                this.telechargementEnCours = false;
            }
        },
    };
}
