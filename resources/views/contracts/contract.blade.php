<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrat de location — {{ $rental->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.5; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #14213f; padding-bottom: 12px; margin-bottom: 20px; }
        .store-name { font-size: 20px; font-weight: bold; color: #14213f; }
        .store-info { font-size: 11px; color: #555; }
        .title { font-size: 16px; font-weight: bold; text-align: center; margin: 8px 0 2px; }
        .ref { text-align: center; font-size: 12px; color: #555; margin-bottom: 18px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .box { width: 48%; border: 1px solid #ccc; border-radius: 6px; padding: 10px; }
        .box h3 { margin: 0 0 6px; font-size: 12px; color: #14213f; }
        .box p { margin: 2px 0; font-size: 11px; }
        table.items { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.items th { background: #14213f; color: #fff; padding: 8px; text-align: left; font-size: 11px; }
        table.items td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 11px; }
        table.items tr:nth-child(even) { background: #f7f7f7; }
        .amounts { width: 260px; margin-left: auto; }
        .amounts td { padding: 4px 0; font-size: 11px; }
        .amounts .total td { border-top: 2px solid #14213f; font-weight: bold; font-size: 13px; }
        .conditions { border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-top: 18px; }
        .conditions h3 { margin: 0 0 6px; font-size: 12px; color: #14213f; }
        .conditions ul { margin: 0; padding-left: 18px; font-size: 10.5px; color: #444; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .signature { width: 40%; text-align: center; font-size: 11px; }
        .signature .line { border-top: 1px solid #333; margin-top: 40px; padding-top: 4px; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #888; }
        .muted { color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="store-name">{{ $store->name }}</div>
            <div class="store-info">
                {{ $store->address }}<br>
                {{ $store->commune }}{{ $store->wilaya ? ', '.$store->wilaya : '' }}<br>
                Tél : {{ $store->phone }} @if($store->phone_secondary) / {{ $store->phone_secondary }} @endif<br>
                @if($store->email) {{ $store->email }} @endif
            </div>
        </div>
        <div class="store-info" style="text-align:right;">
            Fait le {{ $rental->created_at?->format('d/m/Y') }}<br>
            Gérant : {{ $store->manager_name ?? '—' }}
        </div>
    </div>

    <div class="title">CONTRAT DE LOCATION</div>
    <div class="ref">N° {{ $store->contract_prefix ?? 'CTR' }}-{{ str_replace('LOC-', '', $rental->reference) }} — Réf. location : {{ $rental->reference }}</div>

    <div class="row">
        <div class="box">
            <h3>LE PRESTATAIRE (LOUEUR)</h3>
            <p>{{ $store->name }}</p>
            <p>{{ $store->address }}</p>
            <p>{{ $store->commune }}{{ $store->wilaya ? ', '.$store->wilaya : '' }}</p>
            <p>Tél : {{ $store->phone }}</p>
        </div>
        <div class="box">
            <h3>LE CLIENT (LOCATAIRE)</h3>
            <p>{{ $rental->customer->full_name }}</p>
            <p>Tél : {{ $rental->customer->phone }}</p>
            @if($rental->customer->cin) <p>CIN : {{ $rental->customer->cin }}</p> @endif
            @if($rental->customer->address) <p>Adresse : {{ $rental->customer->address }}</p> @endif
        </div>
    </div>

    <p><strong>Période de location :</strong> du <strong>{{ $rental->start_date?->format('d/m/Y') }}</strong> au <strong>{{ $rental->end_date?->format('d/m/Y') }}</strong> ({{ $rental->days }} jour(s))</p>

    @php
        $packGroups = $rental->items->where('is_pack_component', true)->groupBy(fn ($item) => $item->pack_id ?: $item->pack_name);
        $standaloneItems = $rental->items->where('is_pack_component', false);
    @endphp

    @foreach ($packGroups as $packItems)
        @php
            $packLabel = $packItems->first()->pack_name ?: ($packItems->first()->pack?->name ?? 'Pack');
            $packAmount = (int) $packItems->sum('line_total');
        @endphp
        <p><strong>{{ $packLabel }}</strong> — Prix : <strong>{{ number_format($packAmount, 0, ',', ' ') }} {{ $store->currency }}</strong></p>
        <p class="muted" style="margin-top:-6px;">Composition :</p>
        <table class="items" style="margin-top:6px;">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Référence</th>
                    <th>Qté</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($packItems as $item)
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td>{{ $item->product?->reference }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 0, ',', ' ') }} {{ $store->currency }}</td>
                        <td>{{ number_format($item->line_total, 0, ',', ' ') }} {{ $store->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @if($standaloneItems->isNotEmpty())
        <table class="items">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Référence</th>
                    <th>Qté</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($standaloneItems as $item)
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td>{{ $item->product?->reference }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 0, ',', ' ') }} {{ $store->currency }}</td>
                        <td>{{ number_format($item->line_total, 0, ',', ' ') }} {{ $store->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="amounts">
        <tr><td>Sous-total</td><td style="text-align:right;">{{ number_format($rental->subtotal, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        @if($rental->discount > 0)
            <tr><td>Remise</td><td style="text-align:right;">- {{ number_format($rental->discount, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        @endif
        @if($rental->pack_savings > 0)
            <tr><td>Économie packs</td><td style="text-align:right;">- {{ number_format($rental->pack_savings, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        @endif
        @if($store->tax_enabled)
            <tr><td>TVA ({{ $store->tax_rate }}%)</td><td style="text-align:right;">{{ number_format($rental->total * (float)$store->tax_rate / 100, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        @endif
        <tr><td>Caution</td><td style="text-align:right;">{{ number_format($rental->caution, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        <tr class="total"><td>Total à payer</td><td style="text-align:right;">{{ number_format($rental->total, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        <tr><td>Montant payé</td><td style="text-align:right;">{{ number_format($rental->paid_amount, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
        <tr><td>Reste à payer</td><td style="text-align:right;">{{ number_format($rental->remaining, 0, ',', ' ') }} {{ $store->currency }}</td></tr>
    </table>

    <div class="conditions">
        <h3>Conditions générales de location</h3>
        <ul>
            @forelse (collect($store->rental_conditions)->flatten()->filter() as $condition)
                <li>{{ $condition }}</li>
            @empty
                <li>Les articles restent la propriété du loueur et doivent être rendus en bon état à la date convenue.</li>
                <li>En cas de dégradation ou de perte, le client s'engage à rembourser la valeur de l'article.</li>
                <li>Le dépôt de garantie est restitué après contrôle de l'état des articles.</li>
            @endforelse
        </ul>
    </div>

    <div class="signatures">
        <div class="signature">
            <div class="line">Signature du loueur</div>
        </div>
        <div class="signature">
            <div class="line">Signature du client<br><span class="muted">Lu et approuvé</span></div>
        </div>
    </div>

    <div class="footer">Document généré automatiquement par {{ config('app.name') }} — {{ $rental->reference }}</div>
</body>
</html>