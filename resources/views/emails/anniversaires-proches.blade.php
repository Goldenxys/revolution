<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Anniversaires dans 2 jours</title></head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #EBE3D8;max-width:560px;width:100%;">
<tr><td style="background:#AB6715;color:#FFFFFF;padding:16px 28px;font-size:14px;">
    🎂 Anniversaire{{ $clients->count() > 1 ? 's' : '' }} dans 2 jours
</td></tr>

<tr><td style="padding:22px 28px 26px;">
    <p style="margin:0 0 16px;font-size:14px;color:#6B6157;">
        Pensez à proposer la carte anniversaire (+500 F) à la prochaine commande.
    </p>

    @foreach ($clients as $client)
        <div style="padding:10px 0;border-top:1px solid #EBE3D8;">
            <p style="margin:0;font-size:14px;font-weight:600;">{{ $client->nom }}</p>
            <p style="margin:2px 0 0;font-size:13px;color:#6B6157;">
                {{ $client->telephone }} · {{ $client->libelleNaissance() }}
                @if ($client->numero_client) · {{ $client->numero_client }} @endif
            </p>
        </div>
    @endforeach
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
