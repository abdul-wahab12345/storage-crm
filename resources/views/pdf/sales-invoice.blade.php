@php
    $company = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone = \App\Models\Setting::get('company_phone', '');
    $companyEmail = \App\Models\Setting::get('company_email', '');
    $trn = \App\Models\Setting::get('trn_number');
    $bankingInfo = \App\Models\Setting::get('banking_information');

    $logoPath = public_path('images/final-logo.png');
    $logoData = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
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

.header-wrap { background: #fa7e11; padding: 24px 40px; margin-bottom: 28px; }
.header-table { width: 100%; }
.brand-sub { font-size: 11px; color: rgba(255,255,255,0.85); margin-top: 6px; line-height: 1.6; }
.meta-right { text-align: right; vertical-align: top; }
.inv-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.7); }
.inv-number { font-size: 18px; font-weight: 700; color: #fff; margin-top: 2px; }
.inv-date { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 3px; }
.badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-paid     { background: #d1fae5; color: #065f46; }

.body-pad { padding: 0 40px; }

.parties-table { width: 100%; margin-bottom: 24px; }
.party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }
.party-name { font-size: 15px; font-weight: 700; color: #1e293b; }
.party-detail { font-size: 12px; color: #64748b; margin-top: 2px; }

.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.items-table thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.items-table thead th.right { text-align: right; }
.items-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: top; }
.items-table tbody td.right { text-align: right; font-weight: 700; }

.totals-outer { width: 100%; margin-bottom: 32px; }
.totals-inner { width: 260px; float: right; }
.totals-row-table { width: 100%; border-collapse: collapse; }
.totals-row-table td { padding: 6px 0; font-size: 13px; }
.totals-row-table td.label { color: #64748b; }
.totals-row-table td.amount { text-align: right; font-weight: 700; }
.totals-divider { border-top: 2px solid #1e293b; }
.totals-final td { padding-top: 10px; font-size: 17px; font-weight: 700; color: #fa7e11; }
.clearfix { clear: both; }

.footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }
.tc-box { margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.tc-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
.tc-body { font-size: 11px; color: #64748b; line-height: 1.7; }
</style>
</head>
<body>
<div class="page">
    <div class="header-wrap">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle; width:55%;">
                    @if ($logoData)
                        <img src="{{ $logoData }}" style="height:90px; max-width:280px; object-fit:contain;">
                    @else
                        <div style="font-size:26px; font-weight:700; color:#fff;">{{ $company }}</div>
                    @endif
                    <div class="brand-sub">
                        @if ($trn) TRN: {{ $trn }}<br> @endif
                        @if ($companyAddress){{ $companyAddress }}<br>@endif
                        @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;|&nbsp; {{ $companyEmail }}@endif@endif
                    </div>
                </td>
                <td class="meta-right" style="vertical-align:top;">
                    <div class="inv-label">Tax Invoice</div>
                    <div class="inv-number">{{ $invoice->invoice_number }}</div>
                    <div class="inv-date">Issued: {{ $invoice->created_at->format('F j, Y') }}</div>
                    <br>
                    <span class="badge badge-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="body-pad">
        <table class="parties-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:top; width:50%;">
                    <div class="party-label">Bill To</div>
                    <div class="party-name">{{ $invoice->client_name }}</div>
                    @if ($invoice->client_email)<div class="party-detail">{{ $invoice->client_email }}</div>@endif
                </td>
            </tr>
        </table>

        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right" style="width:80px;">Qty</th>
                    <th class="right" style="width:120px;">Price</th>
                    <th class="right" style="width:140px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @if (is_array($invoice->items) && count($invoice->items) > 0)
                    @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item['description'] ?? 'Item' }}</td>
                        <td class="right">{{ $item['quantity'] ?? 1 }}</td>
                        <td class="right">{{ \App\Models\Setting::money($item['price'] ?? 0) }}</td>
                        <td class="right">{{ \App\Models\Setting::money(($item['price'] ?? 0) * ($item['quantity'] ?? 1)) }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <div class="totals-outer">
            <div class="totals-inner">
                <table class="totals-row-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="amount">{{ \App\Models\Setting::money($invoice->subtotal) }}</td>
                    </tr>
                    @if ($invoice->tax > 0)
                    <tr>
                        <td class="label">Tax</td>
                        <td class="amount">{{ \App\Models\Setting::money($invoice->tax) }}</td>
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

        @if ($invoice->notes)
        <div class="tc-box">
            <div class="tc-title">Notes</div>
            <div class="tc-body">{{ $invoice->notes }}</div>
        </div>
        @endif

        @if ($bankingInfo)
        <div class="tc-box" style="margin-top: 15px;">
            <div class="tc-title">Payment Information</div>
            <div class="tc-body">{!! $bankingInfo !!}</div>
        </div>
        @endif

        <div class="footer" style="margin-top: 28px;">
            <p>Thank you for your business. Please make payment by the due date.</p>
            <p style="margin-top:4px;">Generated by {{ $company }} on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</div>
</body>
</html>
