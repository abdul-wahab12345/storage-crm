@php use Illuminate\Support\Carbon; @endphp
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Revenue Report {{ $year }}</title>
<style>
body{font-family:'Segoe UI',sans-serif;color:#1e293b;font-size:13px}
h1{font-size:20px;font-weight:800;color:#4f46e5;margin-bottom:4px}
.meta{color:#64748b;font-size:12px;margin-bottom:24px}
table{width:100%;border-collapse:collapse}
th{background:#f1f5f9;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:2px solid #e2e8f0}
td{padding:10px 14px;border-bottom:1px solid #f1f5f9}
td:last-child{text-align:right;font-weight:600}
.total-row{background:#f8fafc;font-weight:700}
</style></head><body>
<h1>Revenue Report — {{ $year }}</h1>
<div class="meta">Generated {{ now()->format('F j, Y') }}</div>
<table>
    <thead><tr><th>Month</th><th style="text-align:right">Revenue</th></tr></thead>
    <tbody>
        @foreach($chart['labels'] as $i => $label)
        <tr>
            <td>{{ $label }} {{ $year }}</td>
            <td>{{ \App\Models\Setting::money($chart['values'][$i]) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td>Total</td>
            <td>{{ \App\Models\Setting::money(array_sum($chart['values'])) }}</td>
        </tr>
    </tbody>
</table>
</body></html>
