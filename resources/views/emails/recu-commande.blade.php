@php
    use App\Support\Francais;
    $livraison = $commande->estYango()
        ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
        : 'Livreur normal — selon les zones';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Votre commande est validée</title></head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #EBE3D8;max-width:560px;width:100%;">
<tr><td style="padding:28px 32px 8px;">
    <p style="margin:0 0 4px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#AB6715;font-weight:600;">RÉVOLUTION</p>
    <h1 style="margin:0;font-size:20px;">Votre commande est validée</h1>
    <p style="margin:8px 0 0;font-size:13px;color:#6B6157;">Référence {{ $commande->reference }}</p>
</td></tr>

<tr><td style="padding:12px 32px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
        <thead><tr>
            <th align="left" style="border-bottom:1px solid #17120E;padding:6px 4px;font-size:11px;text-transform:uppercase;">Article</th>
            <th align="right" style="border-bottom:1px solid #17120E;padding:6px 4px;font-size:11px;">Qté</th>
            <th align="right" style="border-bottom:1px solid #17120E;padding:6px 4px;font-size:11px;">Total</th>
        </tr></thead>
        <tbody>
        @foreach ($commande->lignes as $ligne)
            <tr>
                <td style="padding:6px 4px;border-bottom:1px solid #EBE3D8;">
                    {{ $ligne->article_nom }}
                    @php $m = collect([$ligne->taille_libelle, $ligne->couleur_nom])->filter()->implode(' · '); @endphp
                    @if ($m)<br><span style="color:#6B6157;font-size:12px;">{{ $m }}</span>@endif
                </td>
                <td align="right" style="padding:6px 4px;border-bottom:1px solid #EBE3D8;">{{ $ligne->quantite }}</td>
                <td align="right" style="padding:6px 4px;border-bottom:1px solid #EBE3D8;white-space:nowrap;">{{ Francais::frais($ligne->prix_unitaire * $ligne->quantite) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</td></tr>

<tr><td style="padding:4px 32px 12px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
        <tr><td>Sous-total articles</td><td align="right">{{ Francais::frais($commande->sous_total) }}</td></tr>
        @if ($commande->remise_montant > 0)
            <tr><td>Remise fidélité − {{ $commande->remise_pourcentage }} %</td><td align="right">− {{ Francais::frais($commande->remise_montant) }}</td></tr>
        @endif
        <tr><td style="color:#8E3914;font-weight:bold;padding-top:4px;">Chiffre d'affaires</td><td align="right" style="color:#8E3914;font-weight:bold;padding-top:4px;">{{ Francais::frais($commande->total_articles) }}</td></tr>
        <tr><td>Frais de livraison</td><td align="right">{{ Francais::frais($commande->frais_livraison) }}</td></tr>
        <tr><td style="font-weight:bold;border-top:1px solid #17120E;padding-top:4px;">Total à payer</td><td align="right" style="font-weight:bold;border-top:1px solid #17120E;padding-top:4px;">{{ Francais::frais($commande->total_a_payer) }}</td></tr>
    </table>
</td></tr>

<tr><td style="padding:8px 32px;font-size:14px;"><strong>Livraison :</strong> {{ $livraison }}</td></tr>

<tr><td style="padding:16px 32px 32px;">
    <a href="{{ $lienRecu }}" style="display:inline-block;background:#8E3914;color:#FFFFFF;text-decoration:none;padding:12px 22px;font-size:14px;">Voir mon reçu (PDF)</a>
    <p style="margin:14px 0 0;font-size:12px;color:#6B6157;">Le reçu est aussi joint à cet e-mail. Merci pour votre confiance.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
