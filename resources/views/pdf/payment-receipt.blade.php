@php
    $company        = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone   = \App\Models\Setting::get('company_phone', '');
    $companyEmail   = \App\Models\Setting::get('company_email', '');
    $invoice        = $payment->invoice;
    $tenant         = $invoice->tenant;

    $logoPath = public_path('images/final-logo.png');
    $logoData = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $stampPath = public_path('images/stamp.png');
    $stampData = file_exists($stampPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($stampPath))
        : null;

    $signaturePath = public_path('images/signature.png');
    $signatureData = file_exists($signaturePath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath))
        : null;

    $spaceLabel = $invoice->lease?->space_details
        ?: ($invoice->lease?->unit?->unit_number ? 'Unit ' . $invoice->lease->unit->unit_number : '—');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Receipt {{ $receiptNumber }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: Helvetica, Arial, sans-serif; }
body { color: #1e293b; background: #fff; font-size: 13px; line-height: 1.6; }

.page { padding: 0; }

/* ── Card wrapper ── */
.card { background: #fff; }

/* ── Header ── */
.header-wrap { background: #F97316; padding: 28px 32px 24px; }
.header-table { width: 100%; }
.brand-sub { font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 5px; line-height: 1.7; }
.receipt-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.65); }
.receipt-number { font-size: 22px; font-weight: 800; color: #fff; margin-top: 3px; letter-spacing: -0.5px; }
.receipt-date { font-size: 11px; color: rgba(255,255,255,0.75); margin-top: 4px; }

/* ── Paid banner ── */
.paid-banner { background: #16a34a; padding: 10px 32px; }
.paid-banner-table { width: 100%; }
.paid-text { font-size: 12px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 2px; }
.paid-check { font-size: 16px; color: #fff; text-align: right; }

/* ── Body ── */
.body { padding: 28px 32px; }

/* ── Amount block ── */
.amount-block { text-align: center; padding: 24px 20px; border-bottom: 1px solid #f1f5f9; margin-bottom: 24px; }
.amount-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; }
.amount-value { font-size: 42px; font-weight: 800; color: #F97316; letter-spacing: -1px; }

/* ── Two-column info ── */
.info-section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9; }
.info-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.info-grid td { padding: 7px 0; font-size: 12px; border-bottom: 1px solid #f8fafc; vertical-align: top; }
.info-grid td.lbl { color: #94a3b8; font-weight: 600; width: 42%; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; padding-top: 9px; }
.info-grid td.val { color: #1e293b; font-weight: 600; font-size: 13px; }

/* ── Divider ── */
.divider { border: none; border-top: 1px solid #f1f5f9; margin: 20px 0; }

/* ── Two column sections ── */
.two-col { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
.two-col td { vertical-align: top; width: 50%; }
.col-pad-right { padding-right: 20px; }
.col-pad-left { padding-left: 20px; border-left: 1px solid #f1f5f9; }

/* ── Balance bar ── */
.balance-bar { background: #f8fafc; border-radius: 6px; padding: 12px 16px; margin-bottom: 24px; }
.balance-table { width: 100%; border-collapse: collapse; }
.balance-table td { font-size: 12px; padding: 3px 0; }
.balance-table td.bl { color: #64748b; }
.balance-table td.br { text-align: right; font-weight: 700; color: #1e293b; }
.balance-total td { font-size: 14px; font-weight: 800; padding-top: 8px; border-top: 1px solid #e2e8f0; margin-top: 6px; }
.balance-total td.bl { color: #1e293b; }
.balance-total td.br { color: #16a34a; }

/* ── Footer ── */
.footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 32px; text-align: center; }
.footer p { font-size: 11px; color: #94a3b8; }
.footer-thank { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 4px; }
</style>
</head>
<body>
<div class="page">
<div class="card">

    {{-- ORANGE HEADER --}}
    <div class="header-wrap">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle; width:55%;">
                    @if ($logoData)
                        <img src="{{ $logoData }}" style="height:52px; max-width:170px;">
                    @else
                        <div style="font-size:22px; font-weight:800; color:#fff;">{{ $company }}</div>
                    @endif
                    <div class="brand-sub">
                        @if ($companyAddress){{ $companyAddress }}<br>@endif
                        @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;·&nbsp; {{ $companyEmail }}@endif@endif
                    </div>
                </td>
                <td style="vertical-align:top; text-align:right;">
                    <div class="receipt-label">Payment Receipt</div>
                    <div class="receipt-number">{{ $receiptNumber }}</div>
                    <div class="receipt-date">{{ $payment->paid_at->format('F j, Y · g:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- GREEN PAID BANNER --}}
    <div class="paid-banner">
        <table class="paid-banner-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="paid-text">&#10003; &nbsp;Payment Confirmed</td>
                <td class="paid-check">{{ $payment->paid_at->format('d M Y') }}</td>
            </tr>
        </table>
    </div>

    {{-- BODY --}}
    <div class="body">

        {{-- AMOUNT --}}
        <div class="amount-block">
            <div class="amount-label">Amount Paid</div>
            <div class="amount-value">{{ \App\Models\Setting::money($payment->amount) }}</div>
        </div>

        {{-- TWO COLUMN: Customer + Payment details --}}
        <table class="two-col" cellpadding="0" cellspacing="0">
            <tr>
                <td class="col-pad-right">
                    <div class="info-section-title">Customer</div>
                    <table class="info-grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="lbl">Name</td>
                            <td class="val">{{ $tenant?->full_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Email</td>
                            <td class="val" style="font-size:11px;">{{ $tenant?->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Phone</td>
                            <td class="val">{{ $tenant?->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Space</td>
                            <td class="val">
                                {{ $spaceLabel }}
                                @if($invoice->lease?->unit?->facility)
                                    <br><span style="font-size:11px; color:#64748b; font-weight:400;">{{ $invoice->lease->unit->facility->name }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="col-pad-left">
                    <div class="info-section-title">Payment Details</div>
                    <table class="info-grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="lbl">Invoice</td>
                            <td class="val">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Method</td>
                            <td class="val">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                        </tr>
                        @if ($payment->reference)
                        <tr>
                            <td class="lbl">Reference</td>
                            <td class="val">{{ $payment->reference }}</td>
                        </tr>
                        @endif
                        @if ($payment->notes)
                        <tr>
                            <td class="lbl">Notes</td>
                            <td class="val" style="font-size:11px;">{{ $payment->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- BALANCE SUMMARY --}}
        <div class="balance-bar">
            <table class="balance-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="bl">Invoice Total</td>
                    <td class="br">{{ \App\Models\Setting::money($invoice->total) }}</td>
                </tr>
                <tr>
                    <td class="bl">Amount Paid</td>
                    <td class="br" style="color:#F97316;">- {{ \App\Models\Setting::money($payment->amount) }}</td>
                </tr>
                <tr class="balance-total">
                    <td class="bl">Remaining Balance</td>
                    <td class="br">{{ \App\Models\Setting::money(max(0, $invoice->balance_due)) }}</td>
                </tr>
            </table>
        </div>

        {{-- SIGNATURE & STAMP --}}
        @if($stampData || $signatureData)
        <div style="text-align: right; margin-top: 30px; padding-right: 20px;">
            <div style="display: inline-block; text-align: center;">
                <div style="height: 90px; margin-bottom: 5px;">
                    @if($signatureData)
                        <img src="{{ $signatureData }}" style="max-height: 60px; max-width: 150px; margin-bottom: -30px; margin-right: -15px; position: relative; z-index: 10;">
                    @endif
                    @if($stampData)
                        <img src="{{ $stampData }}" style="max-height: 85px; max-width: 85px; opacity: 0.85;">
                    @endif
                </div>
                <div style="border-top: 1px solid #cbd5e1; padding-top: 5px; font-weight: bold; font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
                    Authorized Signature
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p class="footer-thank">Thank you for your payment!</p>
        <p>Generated by {{ $company }} &nbsp;·&nbsp; {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

</div>
</div>
</body>
</html>
