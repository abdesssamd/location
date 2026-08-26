<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche retour pack — {{ $rental->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.5; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #14213f; padding-bottom: 12px; margin-bottom: 16px; }
        .store-name { font-size: 20px; font-weight: bold; color: #14213f; }
        .muted { color: #666; }
        .title { font-size: 16px; font-weight: bold; text-align: center; margin: 8px 0 2px; }
        .subtitle { text-align: center; font-size: 12px; color: #555; margin-bottom: 14px; }
        .box { border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
        .box h3 { margin: 0 0 6px; font-size: 12px; color: #14213f; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        table.items th { background: #14213f; color: #fff; padding: 7px; text-align: left; font-size: 10.5px; }
        table.items td { padding: 7px; border-bottom: 1px solid #ddd; font-size: 10.5px; vertical-align: top; }
        .right { text-align: right; }
        .total { font-weight: bold; }
        .footer { margin-top: 18px; text-align: center; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    @php
        $packGroups = $rental->items->where('is_pack_component', true)->groupBy(fn ($item) => $item->pack_id ?: $item->pack_name);
        $damageTotal = (int) $rental->items->sum('return_damage_fee');
    @endphp

    <div class="header">
        <div>
            <div class="store-name">{{ $store->name }}</div>
            <div class="muted">{{ $store->address }} - {{ $store->commune }}{{ $store->wilaya ? ', '.$store->wilaya : '' }}</div>
            <div class="muted">Tel: {{ $store->phone }}</div>
        </div>
        <div class="muted" style="text-align:right;">
            Date: {{ now()->format('d/m/Y H:i') }}<br>
            Ref location: {{ $rental->reference }}
        </div>
    </div>

    <div class="title">FICHE RETOUR PACK</div>
    <div class="subtitle">Client: {{ $rental->customer?->full_name }} | Periode: {{ $rental->start_date?->format('d/m/Y') }} au {{ $rental->end_date?->format('d/m/Y') }}</div>

    @forelse($packGroups as $items)
        @php
            $packName = $items->first()->pack_name ?: ($items->first()->pack?->name ?? 'Pack');
        @endphp
        <div class="box">
            <h3>{{ strtoupper($packName) }}</h3>
            <table class="items">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Reference</th>
                        <th>Qte</th>
                        <th>Etat retour</th>
                        <th>Dommage</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->product?->reference }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ match($item->return_condition) { 'damaged' => 'Endommage', 'cleaning' => 'Nettoyage', 'lost' => 'Perdu', default => 'Bon' } }}</td>
                            <td class="right">{{ number_format((int)$item->return_damage_fee, 0, ',', ' ') }} {{ $store->currency }}</td>
                            <td>{{ $item->return_notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="box">
            <h3>Aucun pack dans cette location</h3>
            <p class="muted">Cette fiche est reservee aux retours de packs. La location ne contient pas de composants de pack.</p>
        </div>
    @endforelse

    <div class="box">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td class="total">Total frais de dommages</td>
                <td class="right total">{{ number_format($damageTotal, 0, ',', ' ') }} {{ $store->currency }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top:30px; display:flex; justify-content:space-between;">
        <div style="width:40%; text-align:center;">
            <div style="border-top:1px solid #333; padding-top:4px; margin-top:40px;">Signature loueur</div>
        </div>
        <div style="width:40%; text-align:center;">
            <div style="border-top:1px solid #333; padding-top:4px; margin-top:40px;">Signature client</div>
        </div>
    </div>

    <div class="footer">Document genere automatiquement par {{ config('app.name') }}</div>
</body>
</html>
