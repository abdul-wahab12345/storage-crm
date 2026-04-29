<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Payments Report</title>
<style>
body{font-family:'Segoe UI',sans-serif;color:#1e293b;font-size:13px}
h1{font-size:20px;font-weight:800;color:#4f46e5;margin-bottom:4px}
.meta{color:#64748b;font-size:12px;margin-bottom:24px}
table{width:100%;border-collapse:collapse}
th{background:#f1f5f9;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:2px solid #e2e8f0}
td{padding:10px 14px;border-bottom:1px solid #f1f5f9}
.right{text-align:right}.bold{font-weight:700}
</style></head><body>
<h1>Payments Collected</h1>
<div class="meta">Period: {{ $month }} &nbsp;|&nbsp; Generated {{ now()->format('F j, Y') }}</div>
<table>
    <thead><tr><th>Method</th><th style="text-align:right">Count</th><th style="text-align:right">Total</th></tr></thead>
    <tbody>
        @foreach($payments as $row)
        <tr>
            <td style="text-transform:capitalize">{{ str_replace('_',' ',$row->method) }}</td>
            <td class="right">{{ $row->count }}</td>
            <td class="right">{{ \App\Models\Setting::money($row->total) }}</td>
        </tr>
        @endforeach
        <tr class="bold">
            <td>Total</td>
            <td class="right">{{ $payments->sum('count') }}</td>
            <td class="right">{{ \App\Models\Setting::money($payments->sum('total')) }}</td>
        </tr>
    </tbody>
</table>
</body></html>
