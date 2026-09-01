<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18181b; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .muted { color: #71717a; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e4e4e7; padding: 6px 8px; text-align: left; }
        th { background: #f4f4f5; }
        .section { margin-top: 18px; }
        .kpi { display: inline-block; width: 30%; margin: 6px 1%; padding: 10px; border: 1px solid #e4e4e7; border-radius: 8px; }
        .kpi .v { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $store?->name ?? 'LouerPro' }}</h1>
    <p class="muted">Rapport du {{ $data['from'] }} au {{ $data['to'] }}</p>

    <h2 class="section">Location</h2>
    <div>
        <div class="kpi"><div class="muted">Chiffre d'affaires location</div><div class="v">{{ money($data['revenue']) }}</div></div>
        <div class="kpi"><div class="muted">Remboursements</div><div class="v">{{ money($data['refunds']) }}</div></div>
        <div class="kpi"><div class="muted">Locations</div><div class="v">{{ $data['rentalCount'] }}</div></div>
    </div>

    <div class="section">
        <h2>Top articles loués</h2>
        <table>
            <thead><tr><th>Article</th><th>Quantité</th><th>Revenu ({{ currency_symbol(store_currency()) }})</th></tr></thead>
            <tbody>
                @foreach ($data['topProducts'] as $p)
                    <tr><td>{{ $p['name'] }}</td><td>{{ $p['qty'] }}</td><td>{{ number_format($p['revenue'], 0, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Top packs loués</h2>
        <table>
            <thead><tr><th>Pack</th><th>Locations</th><th>Revenu ({{ currency_symbol(store_currency()) }})</th></tr></thead>
            <tbody>
                @foreach ($data['topPacks'] as $p)
                    <tr><td>{{ $p['label'] }}</td><td>{{ $p['rentals'] }}</td><td>{{ number_format($p['revenue'], 0, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="section">Vente</h2>
    <div>
        <div class="kpi"><div class="muted">Chiffre d'affaires vente</div><div class="v">{{ money($data['saleRevenue']) }}</div></div>
        <div class="kpi"><div class="muted">Ventes</div><div class="v">{{ $data['saleCount'] }}</div></div>
        <div class="kpi"><div class="muted">Panier moyen</div><div class="v">{{ money($data['saleAverage']) }}</div></div>
    </div>

    <div class="section">
        <h2>Top articles vendus</h2>
        <table>
            <thead><tr><th>Article</th><th>Quantité</th><th>Revenu ({{ currency_symbol(store_currency()) }})</th></tr></thead>
            <tbody>
                @foreach ($data['topSoldProducts'] as $p)
                    <tr><td>{{ $p['name'] }}</td><td>{{ $p['qty'] }}</td><td>{{ number_format($p['revenue'], 0, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="section">Dépenses, achats &amp; bénéfice</h2>
    <div>
        <div class="kpi"><div class="muted">Dépenses</div><div class="v">{{ money($data['expenseTotal']) }}</div></div>
        <div class="kpi"><div class="muted">Achats fournisseurs</div><div class="v">{{ money($data['purchaseTotal']) }}</div></div>
        <div class="kpi"><div class="muted">Bénéfice net</div><div class="v">{{ money($data['netProfit']) }}</div></div>
    </div>

    <div class="section">
        <h2>Dépenses par catégorie</h2>
        <table>
            <thead><tr><th>Catégorie</th><th>Montant ({{ currency_symbol(store_currency()) }})</th></tr></thead>
            <tbody>
                @foreach ($data['expensesByCategory'] as $e)
                    <tr><td>{{ $e['name'] }}</td><td>{{ number_format($e['amount'], 0, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>

