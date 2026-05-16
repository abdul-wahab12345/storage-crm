@php
    $company        = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone   = \App\Models\Setting::get('company_phone', '');
    $companyEmail   = \App\Models\Setting::get('company_email', '');
    $invoice        = $payment->invoice;
    $tenant         = $invoice->tenant;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Receipt {{ $receiptNumber }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; color: #1e293b; background: #fff; font-size: 13px; line-height: 1.6; }
.page { padding: 36px 40px; max-width: 620px; margin: 0 auto; }

.header-table { width: 100%; border-bottom: 3px solid #16a34a; padding-bottom: 18px; margin-bottom: 28px; }
.brand { font-size: 26px; font-weight: 700; color: #16a34a; }
.brand-sub { font-size: 11px; color: #64748b; margin-top: 4px; }
.meta-right { text-align: right; vertical-align: top; }
.receipt-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
.receipt-number { font-size: 18px; font-weight: 700; color: #1e293b; }

.paid-stamp { text-align: center; border: 3px solid #16a34a; color: #16a34a; font-size: 30px; font-weight: 900; text-transform: uppercase; letter-spacing: 4px; padding: 12px 0; margin: 24px 0; border-radius: 4px; }

.info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.info-table td { padding: 6px 10px; font-size: 12px; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
.info-table td.label { color: #64748b; width: 40%; font-weight: 600; }
.info-table td.value { color: #1e293b; font-weight: 500; }

.amount-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 18px 20px; margin: 20px 0; text-align: center; }
.amount-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #16a34a; margin-bottom: 6px; }
.amount-value { font-size: 36px; font-weight: 800; color: #15803d; }

.footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; margin-top: 32px; }
</style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align:top; width:55%;">
                <div class="brand">{{ $company }}</div>
                <div class="brand-sub">
                    @if ($companyAddress){{ $companyAddress }}<br>@endif
                    @if ($companyPhone){{ $companyPhone }}@if($companyEmail) | {{ $companyEmail }}@endif@endif
                </div>
            </td>
            <td class="meta-right" style="vertical-align:top;">
                <div class="receipt-label">Payment Receipt</div>
                <div class="receipt-number">{{ $receiptNumber }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:3px;">{{ $payment->paid_at->format('F j, Y  g:i A') }}</div>
            </td>
        </tr>
    </table>

    {{-- PAID STAMP --}}
    <div class="paid-stamp">Payment Received</div>

    {{-- AMOUNT --}}
    <div class="amount-box">
        <div class="amount-label">Amount Paid</div>
        <div class="amount-value">{{ \App\Models\Setting::money($payment->amount) }}</div>
    </div>

    {{-- TENANT / INVOICE INFO --}}
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Received From</td>
            <td class="value">{{ $tenant?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td class="value">{{ $tenant?->email ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Phone</td>
            <td class="value">{{ $tenant?->phone ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Invoice</td>
            <td class="value">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td class="label">Unit</td>
            <td class="value">
                @if ($invoice->lease?->unit)
                    Unit {{ $invoice->lease->unit->unit_number }}
                    @if ($invoice->lease->unit->facility)— {{ $invoice->lease->unit->facility->name }}@endif
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Payment Method</td>
            <td class="value">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
        </tr>
        @if ($payment->reference)
        <tr>
            <td class="label">Reference</td>
            <td class="value">{{ $payment->reference }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Invoice Total</td>
            <td class="value">{{ \App\Models\Setting::money($invoice->total) }}</td>
        </tr>
        <tr>
            <td class="label">Invoice Balance After</td>
            <td class="value">{{ \App\Models\Setting::money(max(0, $invoice->balance_due)) }}</td>
        </tr>
        @if ($payment->notes)
        <tr>
            <td class="label">Notes</td>
            <td class="value">{{ $payment->notes }}</td>
        </tr>
        @endif
    </table>

    <div class="footer">
        <p>Thank you for your payment!</p>
        <p style="margin-top:4px;">Generated by {{ $company }} on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

</div>
</body>
</html>
