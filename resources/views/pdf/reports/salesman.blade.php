<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Salesman Performance</title>
<style>
body{font-family:'Segoe UI',sans-serif;color:#1e293b;font-size:13px}
h1{font-size:20px;font-weight:800;color:#4f46e5;margin-bottom:4px}
.meta{color:#64748b;font-size:12px;margin-bottom:24px}
table{width:100%;border-collapse:collapse}
th{background:#f1f5f9;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:2px solid #e2e8f0}
td{padding:10px 14px;border-bottom:1px solid #f1f5f9;text-align:center}
td:first-child{text-align:left;font-weight:600}
.conv{color:#059669;font-weight:700}.lost{color:#dc2626}
</style></head><body>
<h1>Salesman Performance</h1>
<div class="meta">Generated {{ now()->format('F j, Y') }}</div>
<table>
    <thead><tr>
        <th>Salesman</th>
        <th>Total</th><th>New</th><th>Contacted</th><th>Qualified</th>
        <th>Converted</th><th>Lost</th>
    </tr></thead>
    <tbody>
        @foreach($salesmen as $s)
        <tr>
            <td>{{ $s->name }}</td>
            <td><strong>{{ $s->leads_total }}</strong></td>
            <td>{{ $s->leads_new }}</td>
            <td>{{ $s->leads_contacted }}</td>
            <td>{{ $s->leads_qualified }}</td>
            <td class="conv">{{ $s->leads_converted }}</td>
            <td class="lost">{{ $s->leads_lost }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body></html>
