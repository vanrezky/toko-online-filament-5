<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 2.5mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, sans-serif; font-size: 6.8pt; line-height: 1.05; }
        .label { border: 1px solid #222; padding: 2mm; width: 100%; }
        .top { width: 100%; border-bottom: 1px solid #222; padding-bottom: 1.2mm; }
        .top td { vertical-align: middle; }
        .store-logo { max-width: 20mm; max-height: 10mm; object-fit: contain; }
        .store-name { font-size: 10.5pt; font-weight: bold; }
        .courier-logo { max-width: 28mm; max-height: 9mm; object-fit: contain; }
        .receipt-no { text-align: right; font-size: 7pt; white-space: nowrap; }
        .code-row { text-align: center; padding: .8mm 0 .5mm; }
        .barcode { width: 68mm; height: 8mm; }
        .barcode-value { font-size: 6pt; letter-spacing: .3pt; }
        .rule { border-top: 1px solid #222; margin: .6mm 0; }
        .meta { width: 100%; }
        .meta td { vertical-align: top; padding: .25mm 0; }
        .meta .right { text-align: right; }
        .receiver { font-size: 7.2pt; font-weight: bold; }
        .address { font-size: 6.2pt; line-height: 1.05; }
        .chip { border: 1px solid #222; padding: .55mm 1mm; font-weight: bold; text-align: center; }
        .summary { width: 100%; border-collapse: collapse; margin-top: .5mm; }
        .summary td { border-top: 1px solid #222; border-bottom: 1px solid #222; padding: .55mm; }
        .summary .right { text-align: right; }
        .notes { font-size: 6.2pt; line-height: 1.05; }
        .product-qr { width: 15mm; text-align: center; vertical-align: top; padding-top: .5mm; }
        .qr { width: 11mm; height: 11mm; }
        .products { width: 100%; border-collapse: collapse; margin-top: .7mm; font-size: 6.2pt; }
        .products th, .products td { border-bottom: 1px solid #555; padding: .35mm .5mm; vertical-align: top; }
        .products th { text-align: left; font-weight: bold; }
        .products .qty { width: 10mm; text-align: right; }
        .products .sku { width: 25mm; }
        .products .amount { width: 25mm; text-align: right; }
        .muted { color: #444; font-size: 5.8pt; }
    </style>
</head>
<body>
    <div class="label">
        <table class="top">
            <tr>
                <td style="width: 22mm;">
                    @if ($receipt->storeLogo)
                        <img class="store-logo" src="{{ $receipt->storeLogo }}" alt="">
                    @endif
                </td>
                <td>
                    <div class="store-name">{{ $receipt->storeName }}</div>
                    @if ($receipt->storeAddress || $receipt->storePhone || $receipt->storeEmail)
                        <div class="muted">
                            {{ implode(' | ', array_filter([$receipt->storeAddress, $receipt->storePhone, $receipt->storeEmail])) }}
                        </div>
                    @endif
                </td>
                <td style="width: 32mm; text-align: right;">
                    @if ($receipt->courierLogo)
                        <img class="courier-logo" src="{{ $receipt->courierLogo }}" alt="">
                    @else
                        <strong>{{ $receipt->courierName }}</strong>
                    @endif
                </td>
                <td class="receipt-no" style="width: 25mm;">No: {{ $receipt->orderCode }}</td>
            </tr>
        </table>

        <div class="code-row">
            @if ($receipt->barcodeImage)
                <img class="barcode" src="{{ $receipt->barcodeImage }}" alt="{{ $receipt->barcodeValue }}">
            @endif
            <div class="barcode-value">{{ $receipt->barcodeValue }}</div>
        </div>

        <div class="rule"></div>

        <table class="meta">
            <tr>
                <td style="width: 72%;">
                    <div class="receiver">Receiver: {{ $receipt->receiverName }}</div>
                    <div class="address">{{ $receipt->receiverPhone ?: '-' }}</div>
                    <div class="address">{{ $receipt->receiverAddress }}</div>
                </td>
                <td class="right"><strong>{{ $receipt->orderDate }}</strong></td>
            </tr>
        </table>

        <table class="meta" style="margin-top: 1mm;">
            <tr>
                <td class="chip">{{ $receipt->warehouseName }}</td>
                <td class="chip">{{ $receipt->courierName }}</td>
                <td class="chip">{{ $receipt->delivery }}</td>
            </tr>
        </table>

        <table class="summary">
            <tr>
                <td>Store: {{ $receipt->storeName }}</td>
                <td>COD: {{ $receipt->cod }}</td>
                <td>Weight: {{ $receipt->weight }}</td>
                <td class="right">Delivery: {{ $receipt->courierName }}</td>
            </tr>
        </table>

        <table class="meta notes">
            <tr>
                <td style="width: 50%;">Buyer Note: {{ $receipt->buyerNote }}</td>
                <td>Seller Note: {{ $receipt->sellerNote }}</td>
                <td class="product-qr" rowspan="2">
                    @if ($receipt->qrImage)
                        <img class="qr" src="{{ $receipt->qrImage }}" alt="QR">
                    @endif
                </td>
            </tr>
        </table>

        <table class="products">
            <thead>
                <tr>
                    <th class="sku"># / Item</th>
                    <th>PSKU / Variation</th>
                    <th class="sku">SKU</th>
                    <th class="qty">Qty</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($receipt->products as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }} / {{ $product['name'] }}</td>
                        <td>{{ $product['code'] }} / {{ $product['variation'] }}</td>
                        <td>{{ $product['sku'] }}</td>
                        <td class="qty">{{ $product['quantity'] }}</td>
                        <td class="amount">{{ $product['amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
