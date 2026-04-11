<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #1e293b; background: #fff; font-size: 14px; line-height: 1.6; }

        .container { max-width: 800px; margin: 0 auto; padding: 40px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 3px solid #4f46e5; padding-bottom: 24px; }
        .brand { font-size: 28px; font-weight: 800; color: #4f46e5; letter-spacing: -0.5px; }
        .brand-sub { font-size: 12px; color: #64748b; margin-top: 4px; }

        .invoice-meta { text-align: right; }
        .invoice-number { font-size: 20px; font-weight: 700; color: #1e293b; }
        .invoice-date { font-size: 13px; color: #64748b; margin-top: 4px; }

        .status-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fecaca; color: #991b1b; }

        .parties { display: flex; justify-content: space-between; margin-bottom: 32px; }
        .party { width: 48%; }
        .party-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
        .party-name { font-size: 16px; font-weight: 600; color: #1e293b; }
        .party-detail { font-size: 13px; color: #64748b; margin-top: 2px; }

        .period-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 32px; display: flex; gap: 40px; }
        .period-item label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; display: block; }
        .period-item span { font-size: 14px; font-weight: 600; color: #1e293b; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #f1f5f9; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
        tbody td:last-child { text-align: right; font-weight: 600; }

        .totals { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .totals-table { width: 280px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .totals-row.total { border-top: 2px solid #1e293b; padding-top: 12px; margin-top: 4px; font-size: 18px; font-weight: 700; color: #4f46e5; }

        .footer { text-align: center; padding-top: 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="brand">StorageCRM</div>
                <div class="brand-sub">
                    @if ($invoice->lease?->unit?->facility)
                        {{ $invoice->lease->unit->facility->name }}<br>
                        {{ $invoice->lease->unit->facility->address }}
                    @endif
                </div>
            </div>
            <div class="invoice-meta">
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="invoice-date">Issued: {{ $invoice->created_at->format('F j, Y') }}</div>
                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>

        <div class="parties">
            <div class="party">
                <div class="party-label">Bill To</div>
                <div class="party-name">{{ $invoice->tenant?->full_name }}</div>
                @if ($invoice->tenant?->email)
                    <div class="party-detail">{{ $invoice->tenant->email }}</div>
                @endif
                @if ($invoice->tenant?->phone)
                    <div class="party-detail">{{ $invoice->tenant->phone }}</div>
                @endif
                @if ($invoice->tenant?->address)
                    <div class="party-detail">{{ $invoice->tenant->address }}</div>
                @endif
            </div>
            <div class="party" style="text-align: right;">
                <div class="party-label">Unit Details</div>
                <div class="party-name">Unit {{ $invoice->lease?->unit?->unit_number }}</div>
                <div class="party-detail">Size: {{ $invoice->lease?->unit?->size }}</div>
                @if ($invoice->lease?->unit?->size_label)
                    <div class="party-detail">{{ $invoice->lease->unit->size_label }}</div>
                @endif
            </div>
        </div>

        <div class="period-box">
            <div class="period-item">
                <label>Billing Period</label>
                <span>{{ $invoice->period_start->format('M j') }} — {{ $invoice->period_end->format('M j, Y') }}</span>
            </div>
            <div class="period-item">
                <label>Due Date</label>
                <span>{{ $invoice->due_date->format('F j, Y') }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Monthly Storage Rent — Unit {{ $invoice->lease?->unit?->unit_number }}
                        <br><span style="font-size: 12px; color: #94a3b8;">{{ $invoice->period_start->format('M j') }} — {{ $invoice->period_end->format('M j, Y') }}</span>
                    </td>
                    <td>${{ number_format($invoice->amount, 2) }}</td>
                </tr>
                @if ($invoice->late_fee > 0)
                    <tr>
                        <td>
                            Late Fee
                            <br><span style="font-size: 12px; color: #94a3b8;">Applied after grace period</span>
                        </td>
                        <td style="color: #dc2626;">${{ number_format($invoice->late_fee, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-table">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>${{ number_format($invoice->amount, 2) }}</span>
                </div>
                @if ($invoice->late_fee > 0)
                    <div class="totals-row">
                        <span>Late Fee</span>
                        <span>${{ number_format($invoice->late_fee, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row total">
                    <span>Total Due</span>
                    <span>${{ number_format($invoice->total, 2) }}</span>
                </div>
            </div>
        </div>

        @if ($invoice->payments->isNotEmpty())
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #64748b;">Payment History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_at->format('M j, Y') }}</td>
                                <td>{{ ucfirst($payment->method) }}</td>
                                <td>{{ $payment->reference ?? '—' }}</td>
                                <td>${{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="footer">
            <p>Thank you for your business. Please make payment by the due date.</p>
            <p style="margin-top: 4px;">Generated by StorageCRM on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>
