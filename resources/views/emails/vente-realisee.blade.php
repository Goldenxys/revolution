@php
    use App\Support\Francais;
    $articles = $commande->lignes
        ->map(fn ($l) => trim($l->article_nom.(collect([$l->taille_libelle, $l->couleur_nom])->filter()->isNotEmpty() ? ' ('.collect([$l->taille_libelle, $l->couleur_nom])->filter()->implode(' · ').')' : '').($l->quantite > 1 ? " ×{$l->quantite}" : '')))
        ->implode('<br>');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Nouvelle vente réalisée</title></head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="520" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #EBE3D8;max-width:520px;width:100%;">
<tr><td style="background:#3F7D4A;color:#FFFFFF;padding:16px 28px;font-size:14px;">✅ Nouvelle vente réalisée</td></tr>
<tr><td style="padding:22px 28px 6px;">
    <p style="margin:0;font-size:26px;font-weight:700;color:#3F7D4A;">{{ Francais::frais($commande->total_articles) }}</p>
    <p style="margin:2px 0 0;font-size:12px;color:#6B6157;">Chiffre d'affaires de cette vente — hors livraison</p>
</td></tr>
<tr><td style="padding:10px 28px;font-size:14px;line-height:1.6;">
    <strong>{{ $client->nom }}</strong> — {{ $commande->reference }}<br>
    {!! $articles !!}
</td></tr>
<tr><td style="padding:6px 28px;font-size:13px;color:#6B6157;">
    Frais de livraison encaissés à part : {{ Francais::frais($commande->frais_livraison) }} — <em>hors chiffre d'affaires</em><br>
    À encaisser auprès de la cliente : {{ Francais::frais($commande->total_a_payer) }}
</td></tr>
<tr><td style="padding:16px 28px 26px;border-top:1px solid #EBE3D8;">
    <p style="margin:0;font-size:13px;color:#6B6157;">Cumul du jour</p>
    <p style="margin:2px 0 0;font-size:20px;font-weight:700;">{{ Francais::frais($caDuJour) }}</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
