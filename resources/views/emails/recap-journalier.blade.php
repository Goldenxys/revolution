@php
    use App\Support\Francais;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Récap RÉVOLUTION</title>
</head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #E9E0D5;max-width:640px;width:100%;">
<tr><td style="background:#8E3914;color:#FFFFFF;padding:20px 28px;text-transform:uppercase;letter-spacing:.08em;font-size:13px;">
Récap du jour — RÉVOLUTION
</td></tr>
<tr><td style="padding:28px;">
<p style="margin:0 0 20px;font-family:Georgia,'Cormorant Garamond',serif;font-size:22px;font-weight:600;">
{{ Francais::dateLongue($date) }}
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
<tr>
<td style="width:25%;padding:10px;text-align:center;border:1px solid #E9E0D5;">
<div style="font-size:24px;font-weight:600;">{{ $indicateurs['commandes'] }}</div>
<div style="font-size:11px;color:#7A6E63;">Commandes</div>
</td>
<td style="width:25%;padding:10px;text-align:center;border:1px solid #E9E0D5;">
<div style="font-size:24px;font-weight:600;">{{ $indicateurs['nouveaux_clients'] }}</div>
<div style="font-size:11px;color:#7A6E63;">Nouveaux clients</div>
</td>
<td style="width:25%;padding:10px;text-align:center;border:1px solid #E9E0D5;">
<div style="font-size:24px;font-weight:600;">{{ $indicateurs['my_verse'] }}</div>
<div style="font-size:11px;color:#7A6E63;">My Verse</div>
</td>
<td style="width:25%;padding:10px;text-align:center;border:1px solid #E9E0D5;">
<div style="font-size:24px;font-weight:600;">{{ Francais::frais($indicateurs['total_frais']) }}</div>
<div style="font-size:11px;color:#7A6E63;">Frais de livraison</div>
</td>
</tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">
<tr style="background:#FBF8F4;">
<td style="padding:8px;border-bottom:1px solid #E9E0D5;color:#7A6E63;">Heure</td>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;color:#7A6E63;">Client</td>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;color:#7A6E63;">Article</td>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;color:#7A6E63;">Livraison</td>
</tr>
@forelse ($commandes as $commande)
<tr>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;">{{ $commande->created_at->format('H:i') }}</td>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;">{{ $commande->client->nom }}</td>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;">{{ $commande->libelle_article }}</td>
<td style="padding:8px;border-bottom:1px solid #E9E0D5;">{{ $commande->commune }} — {{ Francais::frais($commande->frais_livraison) }}</td>
</tr>
@empty
<tr><td colspan="4" style="padding:16px;text-align:center;color:#7A6E63;">Aucune commande ce jour-là.</td></tr>
@endforelse
</table>
</td></tr>
<tr><td style="padding:16px 28px;border-top:1px solid #E9E0D5;font-size:11px;color:#7A6E63;">
RÉVOLUTION — Même ta garde-robe intéresse JÉSUS !
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
