# Carte de fidélité RÉVOLUTION — spécifications de rendu

Template : `template.png` — 2480 x 3508 px (A4, 300 dpi), exporté du PSD, virgule parasite supprimée.
Référence visuelle attendue (8 commandes) : `reference_8_commandes.png`.
Polices : `resources/fonts/Poppins-Bold.ttf`, `Poppins-Light.ttf`.

## Paliers
| Commandes validées | Coches | Réduction sur la commande suivante |
|---|---|---|
| 2 | 1 | -15 % |
| 4 | 2 | -30 % |
| 6 | 3 | -45 % |
| 8 | 4 | -65 % |

## Éléments dynamiques (coordonnées en px sur le template 2480 x 3508)

1. **Nom** : `NOM EN MAJUSCULES,` (la virgule fait partie du texte)
   - Poppins Bold ~100 px, noir #000000, centré horizontalement sur x = 1258
   - Boîte cible des pixels encrés : y de 1303 à 1382
   - Si le nom est trop long (largeur > 1700 px), réduire la taille de police jusqu'à ce qu'il tienne.

2. **Coches** : `check.png` (214 x 200, fond transparent), posée en haut à y = 2626
   - Centres horizontaux des coches 1 à 4 : x = 422, 985, 1526, 2045
   - Nombre de coches = palier atteint (1 à 4)

3. **Texte du bas** : Poppins Light ~61 px, couleur #8E3913, chaque ligne centrée sur x = 1240
   - Première ligne : haut des glyphes à y = 2927 ; interligne = 70 px ; une ligne vide entre les deux paragraphes
   - Lignes (retours à la ligne imposés, ne pas laisser le wrap automatique décider) :
     1. `Vous venez de débloquer -{X} % de réduction sur votre`
     2. `prochaine commande RÉVOLUTION.`
     3. *(vide)*
     4. `Pour bénéficier de votre réduction, il vous suffira de`
     5. `nous envoyer une capture de votre carte de fidélité au`
     6. `moment de votre prochaine commande.`
   - Boîte cible des pixels encrés : x ≈ 422–2073, y 2927–3337

## Validation
Générer la carte pour « DJIEHI CARINE » avec 8 commandes et comparer avec `reference_8_commandes.png` :
les boîtes ci-dessus doivent correspondre à ±10 px près.
