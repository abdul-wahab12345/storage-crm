@php
    $company = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone = \App\Models\Setting::get('company_phone', '');
    $companyEmail = \App\Models\Setting::get('company_email', '');
    $tc = $quote->terms_conditions ?: \App\Models\Setting::get('quote_terms_conditions');

    $logoPath = public_path('images/final-logo.png');
    $logoData = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quote {{ $quote->quote_number }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; color: #1e293b; background: #fff; font-size: 13px; line-height: 1.5; }
.page { padding: 36px 40px; }

/* ── Header ── */
.header-wrap { background: #F97316; padding: 24px 40px; margin-bottom: 28px; }
.header-table { width: 100%; }
.brand-sub { font-size: 11px; color: rgba(255,255,255,0.85); margin-top: 6px; line-height: 1.6; }
.meta-right { text-align: right; vertical-align: top; }
.q-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.7); }
.q-number { font-size: 18px; font-weight: 700; color: #fff; margin-top: 2px; }
.q-date { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 3px; }
.badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px; }
.badge-draft    { background: #f1f5f9; color: #475569; }
.badge-sent     { background: #dbeafe; color: #1e40af; }
.badge-accepted { background: #d1fae5; color: #065f46; }
.badge-rejected { background: #fecaca; color: #991b1b; }

/* ── Parties ── */
.parties-table { width: 100%; margin-bottom: 24px; }
.party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }
.party-name { font-size: 15px; font-weight: 700; color: #1e293b; }
.party-detail { font-size: 12px; color: #64748b; margin-top: 2px; }

/* ── Line items table ── */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.items-table thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.items-table thead th.right { text-align: right; }
.items-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: top; }
.items-table tbody td.right { text-align: right; font-weight: 500; }

/* ── Totals ── */
.totals-inner { width: 280px; float: right; }
.totals-row-table { width: 100%; border-collapse: collapse; }
.totals-row-table td { padding: 6px 0; font-size: 13px; }
.totals-row-table td.label { color: #64748b; }
.totals-row-table td.amount { text-align: right; font-weight: 600; }
.totals-divider { border-top: 2px solid #1e293b; }
.totals-final td { padding-top: 10px; font-size: 17px; font-weight: 800; color: #F97316; }
.clearfix { clear: both; }

/* ── Notes ── */
.notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 16px; margin-bottom: 24px; }
.notes-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }

/* ── Footer ── */
.footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }
.tc-box { margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.tc-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
.tc-body { font-size: 11px; color: #64748b; line-height: 1.7; }
</style>
</head>
<body>
<div class="page" style="padding:0 0 36px 0;">

    {{-- HEADER --}}
    <div class="header-wrap">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle; width:55%;">
                    @if ($logoData)
                        <img src="{{ $logoData }}" style="height:55px; max-width:180px;">
                    @else
                        <div style="font-size:26px; font-weight:700; color:#fff;">{{ $company }}</div>
                    @endif
                    <div class="brand-sub">
                        @if ($companyAddress){{ $companyAddress }}<br>@endif
                        @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;|&nbsp; {{ $companyEmail }}@endif@endif
                    </div>
                </td>
                <td class="meta-right" style="vertical-align:top;">
                    <div class="q-label">Quotation</div>
                    <div class="q-number">{{ $quote->quote_number }}</div>
                    <div class="q-date">Issued: {{ $quote->created_at->format('F j, Y') }}</div>
                    @if ($quote->valid_until)
                        <div class="q-date">Valid Until: {{ $quote->valid_until->format('F j, Y') }}</div>
                    @endif
                    <br>
                    <span class="badge badge-{{ $quote->status }}">{{ ucfirst($quote->status) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div style="padding: 0 40px;">

    {{-- PREPARED FOR / SUBJECT --}}
    <table class="parties-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align:top; width:55%;">
                <div class="party-label">Prepared For</div>
                <div class="party-name">{{ $quote->client_name }}</div>
                @if ($quote->client_email)<div class="party-detail">{{ $quote->client_email }}</div>@endif
                @if ($quote->client_phone)<div class="party-detail">{{ $quote->client_phone }}</div>@endif
            </td>
            <td style="vertical-align:top; text-align:right;">
                <div class="party-label">Subject</div>
                <div class="party-name">{{ $quote->title }}</div>
            </td>
        </tr>
    </table>

    {{-- LINE ITEMS --}}
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right" style="width:80px;">Qty</th>
                <th class="right" style="width:120px;">Unit Price</th>
                <th class="right" style="width:120px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quote->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format($item->quantity, 2) }}</td>
                <td class="right">{{ \App\Models\Setting::money($item->unit_price) }}</td>
                <td class="right">{{ \App\Models\Setting::money($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div style="width:100%; margin-bottom:32px;">
        <div class="totals-inner">
            <table class="totals-row-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="amount">{{ \App\Models\Setting::money($quote->subtotal) }}</td>
                </tr>
                @if ($quote->tax_rate > 0)
                <tr>
                    <td class="label">Tax ({{ number_format($quote->tax_rate, 2) }}%)</td>
                    <td class="amount">{{ \App\Models\Setting::money($quote->tax_amount) }}</td>
                </tr>
                @endif
                <tr class="totals-divider">
                    <td colspan="2" style="padding:0;"></td>
                </tr>
                <tr class="totals-final">
                    <td>Total</td>
                    <td style="text-align:right;">{{ \App\Models\Setting::money($quote->total) }}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="clearfix"></div>

    {{-- NOTES --}}
    @if ($quote->notes)
    <div class="notes-box">
        <div class="notes-label">Notes</div>
        <p style="font-size:13px; color:#475569; line-height:1.6;">{{ $quote->notes }}</p>
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <p>This quote is valid until {{ $quote->valid_until ? $quote->valid_until->format('F j, Y') : 'further notice' }}.</p>
        <p style="margin-top:4px;">Generated by {{ $company }} on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    @if ($tc)
    <div class="tc-box">
        <div class="tc-title">Terms &amp; Conditions</div>
        <div class="tc-body">{{ $tc }}</div>
    </div>
    @endif

    </div>{{-- /body-pad --}}
</div>
</body>
</html>
