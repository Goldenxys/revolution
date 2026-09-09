@php
    use App\Support\Francais;

    $livraison = $commande->estYango()
        ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
        : 'Livreur normal — livraison selon les zones';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { margin: 0; color: #17120E; font-size: 12px; }
    .wrap { padding: 36px 44px; }
    h1 { font-size: 18px; margin: 0 0 2px; letter-spacing: 2px; }
    .muted { color: #6B6157; }
    .row { width: 100%; }
    table { width: 100%; border-collapse: collapse; }
    .lignes th { text-align: left; border-bottom: 1px solid #17120E; padding: 6px 4px; font-size: 11px; text-transform: uppercase; }
    .lignes td { padding: 6px 4px; border-bottom: 1px solid #E9E0D5; }
    .num { text-align: right; white-space: nowrap; }
    .totaux { margin-top: 14px; width: 55%; float: right; }
    .totaux td { padding: 4px 4px; }
    .fort td { font-weight: bold; border-top: 1px solid #17120E; }
    .ca td { color: #8E3914; font-weight: bold; }
    .footer { margin-top: 90px; border-top: 1px solid #E9E0D5; padding-top: 12px; font-size: 10px; }
</style>
</head>
<body>
<div class="wrap">
    <table class="row"><tr>
        <td>
            <h1>RÉVOLUTION</h1>
            <div class="muted">Reçu de commande</div>
        </td>
        <td class="num">
            <strong>{{ $commande->reference }}</strong><br>
            <span class="muted">Validée le {{ optional($commande->validee_at)->format('d/m/Y') ?? $commande->created_at->format('d/m/Y') }}</span>
        </td>
    </tr></table>

    <p style="margin:18px 0 4px;"><strong>{{ $client->nom }}</strong>@if ($client->numero_client) <span class="muted">· {{ $client->numero_client }}</span>@endif</p>
    <p class="muted" style="margin:0 0 18px;">
        {{ $client->telephone }}@if ($client->email) · {{ $client->email }}@endif<br>
        {{ $commande->commune }}@if ($commande->quartier) · {{ $commande->quartier }}@endif
    </p>

    <table class="lignes">
        <thead><tr><th>Article</th><th class="num">Qté</th><th class="num">P.U.</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach ($commande->lignes as $ligne)
            <tr>
                <td>
                    {{ $ligne->article_nom }}
                    @php $meta = collect([$ligne->taille_libelle, $ligne->couleur_nom])->filter()->implode(' · '); @endphp
                    @if ($meta)<br><span class="muted">{{ $meta }}</span>@endif
                </td>
                <td class="num">{{ $ligne->quantite }}</td>
                <td class="num">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                <td class="num">{{ number_format($ligne->prix_unitaire * $ligne->quantite, 0, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr><td>Sous-total articles</td><td class="num">{{ Francais::frais($commande->sous_total) }}</td></tr>
        @if ($commande->remise_montant > 0)
            <tr><td>Remise fidélité − {{ $commande->remise_pourcentage }} %</td><td class="num">− {{ Francais::frais($commande->remise_montant) }}</td></tr>
        @endif
        <tr class="ca"><td>Chiffre d'affaires</td><td class="num">{{ Francais::frais($commande->total_articles) }}</td></tr>
        <tr><td>Frais de livraison</td><td class="num">{{ Francais::frais($commande->frais_livraison) }}</td></tr>
        <tr class="fort"><td>Total à payer</td><td class="num">{{ Francais::frais($commande->total_a_payer) }}</td></tr>
    </table>

    <div style="clear:both;"></div>

    <p style="margin-top:26px;"><strong>Livraison :</strong> {{ $livraison }}</p>

    <div class="footer">
        Merci pour votre confiance. RÉVOLUTION — même ta garde-robe intéresse JÉSUS.<br>
        {{ url('/') }}
    </div>
</div>
</body>
</html>
