@php
    use App\Support\Francais;

    $souhaits = $commande->souhaits_client ?? [];
    $livraisonLigne = $commande->estYango()
        ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
        : 'Livreur normal — selon les zones';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Nouvelle commande à valider</title>
</head>
<body style="margin:0;padding:0;background:#FBF8F4;font-family:'Poppins',Arial,sans-serif;color:#17120E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #EBE3D8;">
<tr><td style="padding:28px 32px 12px;">
    <p style="margin:0 0 4px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#AB6715;font-weight:600;">RÉVOLUTION</p>
    <h1 style="margin:0;font-size:20px;color:#17120E;">Nouvelle commande à valider</h1>
    <p style="margin:8px 0 0;font-size:13px;color:#6B6157;">Référence {{ $commande->reference }} · reçue le {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
</td></tr>

<tr><td style="padding:12px 32px;">
    <h2 style="margin:0 0 6px;font-size:14px;color:#8E3914;">La cliente</h2>
    <p style="margin:0;font-size:14px;line-height:1.6;">
        {{ $client->nom }}<br>
        {{ $client->telephone }}@if ($client->email)<br>{{ $client->email }}@endif<br>
        <span style="color:#6B6157;">
            @if (($client->nb_commandes ?? 0) > 0)
                Cliente fidèle — {{ Francais::ordinal(($client->nb_commandes) + 1) }} commande
            @else
                Nouvelle cliente
            @endif
        </span>
    </p>
</td></tr>

<tr><td style="padding:12px 32px;">
    <h2 style="margin:0 0 6px;font-size:14px;color:#8E3914;">Ses souhaits</h2>
    @forelse ($souhaits as $s)
        <p style="margin:0 0 4px;font-size:14px;line-height:1.5;">
            • {{ $s['article_nom'] ?? 'Article' }}@php $m = array_filter([$s['taille'] ?? null, $s['couleur'] ?? null]); @endphp
            @if ($m) — {{ implode(' · ', $m) }} @endif
            × {{ (int) ($s['quantite'] ?? 1) }}
        </p>
    @empty
        <p style="margin:0;font-size:14px;color:#6B6157;">Aucun article coché.</p>
    @endforelse
    @if ($commande->message_client)
        <p style="margin:8px 0 0;font-size:13px;color:#6B6157;">Précisions : « {{ $commande->message_client }} »</p>
    @endif
</td></tr>

<tr><td style="padding:12px 32px;">
    <h2 style="margin:0 0 6px;font-size:14px;color:#8E3914;">Livraison</h2>
    <p style="margin:0;font-size:14px;line-height:1.6;">
        {{ $commande->commune }}@if ($commande->quartier) · {{ $commande->quartier }}@endif<br>
        {{ $livraisonLigne }}<br>
        <span style="color:#6B6157;">Frais estimés {{ Francais::frais($commande->frais_livraison) }} — hors chiffre d'affaires</span>
    </p>
</td></tr>

<tr><td style="padding:20px 32px 32px;">
    <a href="{{ $url }}" style="display:inline-block;background:#8E3914;color:#FFFFFF;text-decoration:none;padding:12px 22px;font-size:14px;">
        Composer et valider cette commande
    </a>
    <p style="margin:14px 0 0;font-size:12px;color:#6B6157;">Rien n'est comptabilisé tant que vous n'avez pas validé.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
