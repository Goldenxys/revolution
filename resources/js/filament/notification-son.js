/**
 * Son d'alerte quand une nouvelle commande arrive pendant que l'Espace
 * RÉVOLUTION est ouvert. Sonde le nombre de notifications non lues toutes
 * les 12 secondes ; dès qu'il augmente, joue un petit carillon synthétisé
 * (Web Audio API — pas de fichier audio à héberger) et laisse la cloche de
 * notifications Filament (qui sonde de son côté) afficher le détail.
 *
 * Chargé nativement par le panneau Filament via FilamentAsset (voir
 * AdminPanelProvider) — script classique, pas un module ES.
 */
(function () {
    function demarrer() {
        var marqueur = document.querySelector('[data-revo-notifications-compte-url]');
        if (!marqueur) return;

        var url = marqueur.getAttribute('data-revo-notifications-compte-url');
        var INTERVALLE_MS = 12000;
        var dernierCompte = null;
        var audioCtx = null;

        function debloquerAudio() {
            if (!audioCtx) {
                var AudioContextClasse = window.AudioContext || window.webkitAudioContext;
                if (AudioContextClasse) audioCtx = new AudioContextClasse();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume().catch(function () {});
            }
        }
        document.addEventListener('click', debloquerAudio, { once: true });
        document.addEventListener('keydown', debloquerAudio, { once: true });

        function jouerCarillon() {
            if (!audioCtx) return;
            var maintenant = audioCtx.currentTime;
            [[880, 0], [1108.73, 0.12]].forEach(function (paire) {
                var frequence = paire[0];
                var delai = paire[1];
                var oscillateur = audioCtx.createOscillator();
                var gain = audioCtx.createGain();
                oscillateur.type = 'sine';
                oscillateur.frequency.value = frequence;
                gain.gain.setValueAtTime(0, maintenant + delai);
                gain.gain.linearRampToValueAtTime(0.18, maintenant + delai + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, maintenant + delai + 0.5);
                oscillateur.connect(gain);
                gain.connect(audioCtx.destination);
                oscillateur.start(maintenant + delai);
                oscillateur.stop(maintenant + delai + 0.55);
            });
        }

        function verifier() {
            fetch(url, { headers: { Accept: 'application/json' } })
                .then(function (reponse) {
                    return reponse.ok ? reponse.json() : null;
                })
                .then(function (donnees) {
                    if (!donnees) return;
                    var compte = Number(donnees.compte || 0);
                    if (dernierCompte !== null && compte > dernierCompte) {
                        jouerCarillon();
                    }
                    dernierCompte = compte;
                })
                .catch(function () {
                    // Silencieux : une sonde ratée de temps en temps n'est pas
                    // grave, la suivante rattrapera l'état réel.
                });
        }

        verifier();
        setInterval(verifier, INTERVALLE_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', demarrer);
    } else {
        demarrer();
    }
})();
