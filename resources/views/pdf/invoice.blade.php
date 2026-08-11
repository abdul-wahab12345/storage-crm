@php
    $company = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone = \App\Models\Setting::get('company_phone', '');
    $companyEmail = \App\Models\Setting::get('company_email', '');
    $tc = \App\Models\Setting::get('terms_conditions');

    $logoPath = public_path('images/final-logo.png');
    $logoData = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $spaceLabel = $invoice->lease?->space_details
        ?: ($invoice->lease?->unit?->unit_number ? 'Unit ' . $invoice->lease->unit->unit_number : '—');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: Helvetica, Arial, sans-serif; }
body { color: #1e293b; background: #fff; font-size: 13px; line-height: 1.5; }
.page { padding: 0 0 36px 0; }

/* ── Header ── */
.header-wrap { background: #EA580C; padding: 24px 40px; margin-bottom: 28px; }
.header-table { width: 100%; }
.brand-sub { font-size: 11px; color: rgba(255,255,255,0.85); margin-top: 6px; line-height: 1.6; }
.meta-right { text-align: right; vertical-align: top; }
.inv-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.7); }
.inv-number { font-size: 18px; font-weight: 700; color: #fff; margin-top: 2px; }
.inv-date { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 3px; }
.badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-paid     { background: #d1fae5; color: #065f46; }
.badge-overdue  { background: #fecaca; color: #991b1b; }
.badge-cancelled{ background: #f1f5f9; color: #475569; }

/* ── Body padding ── */
.body-pad { padding: 0 40px; }

/* ── Parties ── */
.parties-table { width: 100%; margin-bottom: 24px; }
.party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }
.party-name { font-size: 15px; font-weight: 700; color: #1e293b; }
.party-detail { font-size: 12px; color: #64748b; margin-top: 2px; }

/* ── Period box ── */
.period-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 12px 16px; margin-bottom: 24px; }
.period-table { width: 100%; }
.period-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
.period-value { font-size: 13px; font-weight: 700; color: #1e293b; margin-top: 2px; }

/* ── Line items table ── */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.items-table thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.items-table thead th.right { text-align: right; }
.items-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: top; }
.items-table tbody td.right { text-align: right; font-weight: 700; }
.item-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* ── Totals ── */
.totals-outer { width: 100%; margin-bottom: 32px; }
.totals-inner { width: 260px; float: right; }
.totals-row-table { width: 100%; border-collapse: collapse; }
.totals-row-table td { padding: 6px 0; font-size: 13px; }
.totals-row-table td.label { color: #64748b; }
.totals-row-table td.amount { text-align: right; font-weight: 700; }
.totals-divider { border-top: 2px solid #1e293b; }
.totals-final td { padding-top: 10px; font-size: 17px; font-weight: 700; color: #EA580C; }
.clearfix { clear: both; }

/* ── Payments ── */
.section-title { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

/* ── Footer ── */
.footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }
.tc-box { margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.tc-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
.tc-body { font-size: 11px; color: #64748b; line-height: 1.7; }
</style>
</head>
<body>
<div class="page">

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
                        @if ($invoice->lease?->unit?->facility)
                            {{ $invoice->lease->unit->facility->name }}<br>
                        @endif
                        @if ($companyAddress){{ $companyAddress }}<br>@endif
                        @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;|&nbsp; {{ $companyEmail }}@endif@endif
                    </div>
                </td>
                <td class="meta-right" style="vertical-align:top;">
                    <div class="inv-label">Invoice</div>
                    <div class="inv-number">{{ $invoice->invoice_number }}</div>
                    <div class="inv-date">Issued: {{ $invoice->created_at->format('F j, Y') }}</div>
                    <br>
                    <span class="badge badge-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="body-pad">

        {{-- BILL TO / SPACE DETAIL --}}
        <table class="parties-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:top; width:50%;">
                    <div class="party-label">Bill To</div>
                    <div class="party-name">{{ $invoice->tenant?->full_name }}</div>
                    @if ($invoice->tenant?->email)<div class="party-detail">{{ $invoice->tenant->email }}</div>@endif
                    @if ($invoice->tenant?->phone)<div class="party-detail">{{ $invoice->tenant->phone }}</div>@endif
                    @if ($invoice->tenant?->address)<div class="party-detail">{{ $invoice->tenant->address }}</div>@endif
                </td>
                <td style="vertical-align:top; text-align:right;">
                    <div class="party-label">Space Detail</div>
                    <div class="party-name">{{ $spaceLabel }}</div>
                </td>
            </tr>
        </table>

        {{-- PERIOD BOX --}}
        <div class="period-box">
            <table class="period-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:50%;">
                        <div class="period-label">Billing Period</div>
                        <div class="period-value">{{ $invoice->period_start->format('M j') }} — {{ $invoice->period_end->format('M j, Y') }}</div>
                    </td>
                    <td>
                        <div class="period-label">Due Date</div>
                        <div class="period-value">{{ $invoice->due_date->format('F j, Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- LINE ITEMS --}}
        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right" style="width:160px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Monthly Storage Rent — {{ $spaceLabel }}
                        <div class="item-sub">{{ $invoice->period_start->format('M j') }} — {{ $invoice->period_end->format('M j, Y') }}</div>
                    </td>
                    <td class="right">{{ \App\Models\Setting::money($invoice->amount) }}</td>
                </tr>
                @if ($invoice->late_fee > 0)
                <tr>
                    <td>
                        Late Fee
                        <div class="item-sub">Applied after grace period</div>
                    </td>
                    <td class="right" style="color:#dc2626;">{{ \App\Models\Setting::money($invoice->late_fee) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        {{-- TOTALS --}}
        <div class="totals-outer">
            <div class="totals-inner">
                <table class="totals-row-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="amount">{{ \App\Models\Setting::money($invoice->amount) }}</td>
                    </tr>
                    @if ($invoice->late_fee > 0)
                    <tr>
                        <td class="label">Late Fee</td>
                        <td class="amount" style="color:#dc2626;">{{ \App\Models\Setting::money($invoice->late_fee) }}</td>
                    </tr>
                    @endif
                    <tr class="totals-divider">
                        <td colspan="2" style="padding:0;"></td>
                    </tr>
                    <tr class="totals-final">
                        <td>Total Due</td>
                        <td style="text-align:right;">{{ \App\Models\Setting::money($invoice->total) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="clearfix"></div>

        {{-- PAYMENT HISTORY --}}
        @if ($invoice->payments->isNotEmpty())
        <div style="margin-bottom:28px;">
            <div class="section-title">Payment History</div>
            <table class="items-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('M j, Y') }}</td>
                        <td>{{ ucfirst($payment->method) }}</td>
                        <td>{{ $payment->reference ?? '—' }}</td>
                        <td class="right">{{ \App\Models\Setting::money($payment->amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- FOOTER --}}
        <div class="footer">
            <p>Thank you for your business. Please make payment by the due date.</p>
            <p style="margin-top:4px;">Generated by {{ $company }} on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

        @if ($tc)
        <div class="tc-box">
            <div class="tc-title">Terms &amp; Conditions</div>
            <div class="tc-body">{{ $tc }}</div>
        </div>
        @endif

    </div>
</div>
</body>
</html>
