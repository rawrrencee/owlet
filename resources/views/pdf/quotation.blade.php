<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->quotation_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
        }
        .container {
            padding: 30px 40px;
        }

        /* Header */
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header-row {
            width: 100%;
        }
        .header-row td {
            vertical-align: top;
        }
        .company-logo {
            max-height: 60px;
            max-width: 180px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .company-details {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        .quotation-title {
            text-align: right;
        }
        .quotation-title h1 {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .quotation-number {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .quotation-meta {
            font-size: 10px;
            color: #666;
            margin-top: 8px;
        }
        .quotation-meta td {
            padding: 1px 0;
        }
        .meta-label {
            color: #999;
            padding-right: 8px;
            text-align: right;
        }

        /* Customer */
        .customer-section {
            margin-bottom: 20px;
        }
        .section-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 4px;
        }
        .customer-name {
            font-weight: bold;
            font-size: 12px;
        }
        .customer-details {
            font-size: 10px;
            color: #666;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead th {
            background-color: #333;
            color: #fff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table thead th.text-right {
            text-align: right;
        }
        .items-table thead th.text-center {
            text-align: center;
        }
        .items-table tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        .items-table tbody tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .product-name {
            font-weight: 600;
        }
        .product-number {
            font-size: 9px;
            color: #999;
        }
        .offer-tag {
            display: inline-block;
            background-color: #e8f5e9;
            color: #2e7d32;
            font-size: 8px;
            padding: 1px 4px;
            border-radius: 2px;
        }
        .discount-text {
            color: #2e7d32;
            font-size: 9px;
        }

        /* Totals */
        .totals-section {
            margin-bottom: 20px;
        }
        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 10px;
            font-size: 10px;
        }
        .totals-table .label {
            color: #666;
        }
        .totals-table .amount {
            text-align: right;
            font-weight: 500;
        }
        .totals-table .discount-row .amount {
            color: #2e7d32;
        }
        .totals-table .total-row td {
            border-top: 2px solid #333;
            font-size: 13px;
            font-weight: bold;
            padding-top: 8px;
            color: #333;
        }
        .currency-header {
            font-weight: bold;
            font-size: 11px;
            color: #333;
            padding-bottom: 6px;
        }

        /* Footer sections */
        .footer-section {
            margin-bottom: 16px;
        }
        .footer-section h3 {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
        }
        .footer-section p, .footer-section div {
            font-size: 10px;
            color: #555;
        }
        .payment-modes {
            display: inline;
        }
        .payment-mode-tag {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1565c0;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 2px;
            margin-right: 4px;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <table class="header-row" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="60%">
                        @if($logoBase64 && $quotation->show_company_logo)
                            <img src="{{ $logoBase64 }}" class="company-logo" alt="">
                            <br>
                        @endif
                        @if($quotation->company)
                            <div class="company-name">{{ $quotation->company->company_name }}</div>
                            <div class="company-details">
                                @if($quotation->show_company_uen && $quotation->company->registration_number)
                                    UEN: {{ $quotation->company->registration_number }}<br>
                                @endif
                                @if($quotation->show_company_address)
                                    @if($quotation->company->address_1){{ $quotation->company->address_1 }}<br>@endif
                                    @if($quotation->company->address_2){{ $quotation->company->address_2 }}<br>@endif
                                    @if($quotation->company->city || $quotation->company->postal_code)
                                        {{ $quotation->company->city }}{{ $quotation->company->city && $quotation->company->postal_code ? ' ' : '' }}{{ $quotation->company->postal_code }}<br>
                                    @endif
                                @endif
                                @if($quotation->company->email){{ $quotation->company->email }}<br>@endif
                                @if($quotation->company->phone_number){{ $quotation->company->phone_number }}@endif
                            </div>
                        @endif
                    </td>
                    <td width="40%" class="quotation-title">
                        <h1>Quotation</h1>
                        <div class="quotation-number">{{ $quotation->quotation_number }}</div>
                        <table class="quotation-meta" cellpadding="0" cellspacing="0" style="margin-left: auto;">
                            <tr>
                                <td class="meta-label">Date:</td>
                                <td>{{ $quotation->created_at->format('d M Y') }}</td>
                            </tr>
                            @if($quotation->validity_date)
                            <tr>
                                <td class="meta-label">Valid Until:</td>
                                <td>{{ $quotation->validity_date->format('d M Y') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="meta-label">Status:</td>
                                <td>{{ $quotation->status->label() }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Customer -->
        @if($quotation->customer)
        <div class="customer-section">
            <div class="section-label">Bill To</div>
            <div class="customer-name">{{ $quotation->customer->full_name }}</div>
            <div class="customer-details">
                @if($quotation->customer->email){{ $quotation->customer->email }}<br>@endif
                @if($quotation->customer->phone){{ $quotation->customer->phone }}@endif
            </div>
        </div>
        @endif

        <!-- Line Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Product</th>
                    <th style="width: 10%;">Currency</th>
                    <th class="text-right" style="width: 15%;">Unit Price</th>
                    <th class="text-center" style="width: 8%;">Qty</th>
                    <th class="text-right" style="width: 12%;">Discount</th>
                    <th class="text-right" style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="product-name">{{ $item->product?->product_name ?? 'Unknown' }}</div>
                        <div class="product-number">
                            {{ $item->product?->product_number ?? '' }}
                            @if($item->product?->variant_name) - {{ $item->product->variant_name }}@endif
                        </div>
                        @if($item->offer_name)
                            <span class="offer-tag">{{ $item->offer_name }}</span>
                        @endif
                    </td>
                    <td>{{ $item->currency?->code ?? '-' }}</td>
                    <td class="text-right">{{ $item->currency?->symbol ?? '' }}{{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        @if((float)$item->line_discount > 0)
                            <span class="discount-text">-{{ $item->currency?->symbol ?? '' }}{{ number_format((float)$item->line_discount, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right" style="font-weight: 600;">{{ $item->currency?->symbol ?? '' }}{{ number_format((float)$item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            @foreach($totals as $total)
                <table class="totals-table">
                    @if(count($totals) > 1)
                    <tr>
                        <td colspan="2" class="currency-header">{{ $total['currency_code'] }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="amount">{{ $total['currency_symbol'] }}{{ number_format((float)$total['subtotal'], 2) }}</td>
                    </tr>
                    @if((float)$total['discount'] > 0)
                    <tr class="discount-row">
                        <td class="label">Discount</td>
                        <td class="amount">-{{ $total['currency_symbol'] }}{{ number_format((float)$total['discount'], 2) }}</td>
                    </tr>
                    @endif
                    @if((float)$total['tax'] > 0)
                    <tr>
                        <td class="label">Tax{{ $quotation->tax_inclusive ? ' (incl.)' : '' }}</td>
                        <td class="amount">{{ $total['currency_symbol'] }}{{ number_format((float)$total['tax'], 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label">Total</td>
                        <td class="amount">{{ $total['currency_symbol'] }}{{ number_format((float)$total['total'], 2) }}</td>
                    </tr>
                </table>
                @if(!$loop->last)
                    <br>
                @endif
            @endforeach
        </div>

        <!-- Terms & Conditions -->
        @if($quotation->terms_and_conditions)
        <div class="footer-section">
            <h3>Terms & Conditions</h3>
            <div>{!! $quotation->terms_and_conditions !!}</div>
        </div>
        @endif

        <!-- Payment Terms -->
        @if($quotation->payment_terms)
        <div class="footer-section">
            <h3>Payment Terms</h3>
            <p>{{ $quotation->payment_terms }}</p>
        </div>
        @endif

        <!-- External Notes -->
        @if($quotation->external_notes)
        <div class="footer-section">
            <h3>Notes</h3>
            <p>{{ $quotation->external_notes }}</p>
        </div>
        @endif

        <!-- Payment Modes -->
        @if($quotation->paymentModes->isNotEmpty())
        <div class="footer-section">
            <h3>Accepted Payment Modes</h3>
            <div class="payment-modes">
                @foreach($quotation->paymentModes as $pm)
                    <span class="payment-mode-tag">{{ $pm->name }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</body>
</html>
