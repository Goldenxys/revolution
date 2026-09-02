@php
    use App\Support\Francais;

    $estCatalogue = $commande->utilise_catalogue;
    $premiereLigne = $estCatalogue ? $commande->lignes->first() : null;

    if ($estCatalogue) {
        $articleLigne = $commande->lignes
            ->map(function ($ligne) {
                $details = collect([$ligne->taille_libelle, $ligne->couleur_nom])->filter()->implode(' · ');

                return trim($ligne->article_nom.($details ? " · {$details}" : '').($ligne->quantite > 1 ? " ×{$ligne->quantite}" : ''));
            })
            ->implode('<br>');
    } else {
        $tailleLigne = $commande->taille ? ' · taille '.$commande->taille : '';
        $couleurLigne = $commande->couleur ? ' · couleur '.$commande->couleur : '';

        $articleLigne = $commande->estMyVerse()
            ? trim('Tee-shirt MY VERSE'.$tailleLigne.$couleurLigne)
            : trim(($commande->type_article ?? 'Article').' « '.($commande->nom_article ?? '').' »'.$tailleLigne.$couleurLigne);
    }

    $verset = $estCatalogue ? $premiereLigne?->verset : $commande->verset_texte;
    $referenceVerset = $estCatalogue ? null : $commande->verset_reference;
    $modele = $estCatalogue ? $premiereLigne?->modele : null;

    $livraisonLigne = $commande->estYango()
        ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
        : 'Livreur normal — selon les zones';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Commande RÉVOLUTION</title>
</head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #E9E0D5;max-width:600px;width:100%;">
<tr><td style="background:#8E3914;color:#FFFFFF;padding:20px 28px;text-transform:uppercase;letter-spacing:.08em;font-size:13px;">
Nouvelle commande RÉVOLUTION
</td></tr>
<tr><td style="padding:28px;">
<p style="margin:0 0 18px;font-family:Georgia,'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:#17120E;">
Commande {{ $commande->reference }}
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.7;">
<tr><td style="color:#7A6E63;width:170px;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Collection</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $commande->libelleCollection() }}</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Client</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $client->nom }} ({{ Francais::ordinal($commande->numero_commande_client) }} commande)</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Téléphone</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $client->telephone }}</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Email</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $client->email ?: '—' }}</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Article</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{!! $articleLigne !!}</td></tr>

@if($modele)
<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Modèle</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $modele }}</td></tr>
@endif

@if($referenceVerset || $verset)
<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Verset</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $referenceVerset ?: '—' }}</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Texte du verset</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $verset ?: '—' }}</td></tr>
@endif

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Livraison</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $livraisonLigne }}</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Commune</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $commande->commune }} — frais : {{ Francais::frais($commande->frais_livraison) }}</td></tr>

<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Quartier / repère</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">{{ $commande->quartier ?: '—' }}</td></tr>

@if($commande->total !== null)
<tr><td style="color:#7A6E63;vertical-align:top;padding:6px 0;border-bottom:1px solid #E9E0D5;">Total</td>
<td style="padding:6px 0;border-bottom:1px solid #E9E0D5;">
{{ Francais::frais($commande->total) }}
@if($commande->remise_pourcentage)
    (dont −{{ $commande->remise_pourcentage }} % fidélité, soit −{{ Francais::frais($commande->remise_montant) }})
@endif
</td></tr>
@endif
</table>

<p style="margin:20px 0 0;font-size:12px;color:#7A6E63;">
Reçue le {{ $commande->created_at->timezone(config('app.timezone'))->format('d/m/Y') }} à {{ $commande->created_at->timezone(config('app.timezone'))->format('H:i') }}
</p>
</td></tr>
<tr><td style="padding:16px 28px;border-top:1px solid #E9E0D5;font-size:11px;color:#7A6E63;">
RÉVOLUTION — Même ta garde-robe intéresse JÉSUS !
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
