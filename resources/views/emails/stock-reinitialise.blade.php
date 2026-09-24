<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Stock réinitialisé</title></head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #EBE3D8;max-width:560px;width:100%;">
<tr><td style="background:#B3261E;color:#FFFFFF;padding:16px 28px;font-size:14px;">⚠️ Stock réinitialisé</td></tr>

<tr><td style="padding:22px 28px 6px;">
    <p style="margin:0;font-size:15px;line-height:1.6;">
        Vous venez de réinitialiser le stock complet depuis « Mon stock ».
        <strong>{{ $nombreDesignations }}</strong> désignation{{ $nombreDesignations > 1 ? 's' : '' }} (taille, couleur, quantité)
        {{ $nombreDesignations > 1 ? 'ont été supprimées' : 'a été supprimée' }}, représentant
        <strong>{{ $totalPieces }}</strong> pièce{{ $totalPieces > 1 ? 's' : '' }} précédemment enregistrée{{ $totalPieces > 1 ? 's' : '' }}.
    </p>
    <p style="margin:10px 0 0;font-size:13px;color:#6B6157;">
        Ces articles sont désormais masqués du site public jusqu'à un nouvel enregistrement de stock (« Entrée de stock », dans Mon stock).
    </p>
</td></tr>

<tr><td style="padding:16px 28px 26px;border-top:1px solid #EBE3D8;">
    <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#6B6157;text-transform:uppercase;letter-spacing:.04em;">
        Détail de ce qui a été supprimé
    </p>

    @foreach ($articles as $article)
        <div style="margin-bottom:14px;">
            <p style="margin:0 0 4px;font-size:14px;font-weight:600;">{{ $article['nom'] }}</p>
            <p style="margin:0;font-size:13px;color:#6B6157;line-height:1.6;">
                @foreach ($article['lignes'] as $ligne)
                    {{ collect([$ligne['taille'], $ligne['couleur']])->filter()->implode(' / ') ?: 'Taille unique' }}
                    — {{ $ligne['stock'] ?? 0 }} pièce{{ ($ligne['stock'] ?? 0) > 1 ? 's' : '' }}<br>
                @endforeach
            </p>
        </div>
    @endforeach
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
