@php
    $souhaits = $commande->souhaits_client ?? [];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Votre demande est bien reçue</title></head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #EBE3D8;max-width:560px;width:100%;">
<tr><td style="padding:28px 32px 8px;">
    <p style="margin:0 0 4px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#AB6715;font-weight:600;">RÉVOLUTION</p>
    <h1 style="margin:0;font-size:20px;">Votre demande est bien reçue</h1>
</td></tr>
<tr><td style="padding:8px 32px;font-size:14px;line-height:1.6;">
    Merci {{ $client->nom }}. Nous vérifions le contenu et nous vous confirmons le montant définitif très vite.
    <span style="color:#6B6157;">Référence {{ $commande->reference }}.</span>
</td></tr>
<tr><td style="padding:8px 32px 4px;">
    <h2 style="margin:0 0 6px;font-size:14px;color:#8E3914;">Ce que vous avez demandé</h2>
    @foreach ($souhaits as $s)
        <p style="margin:0 0 4px;font-size:14px;">
            • {{ $s['article_nom'] ?? 'Article' }}@php $m = array_filter([$s['taille'] ?? null, $s['couleur'] ?? null]); @endphp
            @if ($m) — {{ implode(' · ', $m) }} @endif × {{ (int) ($s['quantite'] ?? 1) }}
        </p>
    @endforeach
    @if ($commande->message_client)
        <p style="margin:8px 0 0;font-size:13px;color:#6B6157;">Vos précisions : « {{ $commande->message_client }} »</p>
    @endif
</td></tr>
<tr><td style="padding:16px 32px 28px;font-size:13px;color:#6B6157;">
    Le montant définitif, remise fidélité comprise, vous sera confirmé par la gérante. Vous recevrez alors votre reçu.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
