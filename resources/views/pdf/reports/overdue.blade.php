<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Overdue Invoices</title>
<style>
body{font-family:'Segoe UI',sans-serif;color:#1e293b;font-size:13px}
h1{font-size:20px;font-weight:800;color:#dc2626;margin-bottom:4px}
.meta{color:#64748b;font-size:12px;margin-bottom:24px}
table{width:100%;border-collapse:collapse}
th{background:#fef2f2;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#991b1b;border-bottom:2px solid #fecaca}
td{padding:10px 14px;border-bottom:1px solid #f1f5f9}
.amt{color:#dc2626;font-weight:700;text-align:right}
.days{color:#dc2626;text-align:right}
</style></head><body>
<h1>Overdue Invoices</h1>
<div class="meta">Generated {{ now()->format('F j, Y') }} &nbsp;|&nbsp; {{ $overdue->count() }} overdue</div>
<table>
    <thead><tr><th>Invoice #</th><th>Tenant</th><th>Unit</th><th style="text-align:right">Amount</th><th>Due Date</th><th style="text-align:right">Days Overdue</th></tr></thead>
    <tbody>
        @foreach($overdue as $inv)
        <tr>
            <td>{{ $inv->invoice_number }}</td>
            <td>{{ $inv->tenant?->full_name }}</td>
            <td>{{ $inv->lease?->unit?->unit_number ?? '—' }}</td>
            <td class="amt">{{ \App\Models\Setting::money($inv->total) }}</td>
            <td>{{ $inv->due_date?->format('M j, Y') }}</td>
            <td class="days">{{ abs((int) now()->diffInDays($inv->due_date)) }}d</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body></html>
