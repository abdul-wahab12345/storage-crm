@php
    $company        = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone   = \App\Models\Setting::get('company_phone', '');
    $companyEmail   = \App\Models\Setting::get('company_email', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lease Agreement — {{ $lease->tenant->full_name }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; color: #1e293b; background: #fff; font-size: 13px; line-height: 1.6; }
.page { padding: 40px 48px; }

.header-table { width: 100%; border-bottom: 3px solid #4f46e5; padding-bottom: 18px; margin-bottom: 28px; }
.brand { font-size: 26px; font-weight: 700; color: #4f46e5; }
.brand-sub { font-size: 11px; color: #64748b; margin-top: 4px; }
.doc-title { font-size: 20px; font-weight: 700; color: #1e293b; text-align: right; }
.doc-sub { font-size: 11px; color: #64748b; text-align: right; margin-top: 4px; }

h2 { font-size: 13px; font-weight: 700; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.5px; margin: 22px 0 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }

.info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.info-table td { padding: 5px 8px; font-size: 12px; vertical-align: top; }
.info-table td.label { color: #64748b; width: 38%; font-weight: 600; }
.info-table td.value { color: #1e293b; }

.clause { margin-bottom: 12px; font-size: 12px; }
.clause-num { font-weight: 700; color: #4f46e5; margin-right: 4px; }

.sig-table { width: 100%; margin-top: 48px; border-collapse: collapse; }
.sig-table td { padding: 0 20px 0 0; vertical-align: top; width: 50%; }
.sig-line { border-top: 1px solid #1e293b; padding-top: 6px; margin-top: 48px; font-size: 11px; color: #64748b; }

.footer { text-align: center; margin-top: 36px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; }
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
            <td style="vertical-align:top;">
                <div class="doc-title">Storage Lease Agreement</div>
                <div class="doc-sub">Agreement #{{ str_pad($lease->id, 6, '0', STR_PAD_LEFT) }}</div>
            </td>
        </tr>
    </table>

    {{-- PARTIES --}}
    <h2>1. Parties</h2>
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Landlord / Facility</td>
            <td class="value">{{ $lease->unit->facility->name ?? $company }}</td>
        </tr>
        <tr>
            <td class="label">Tenant Name</td>
            <td class="value">{{ $lease->tenant->full_name }}</td>
        </tr>
        <tr>
            <td class="label">Tenant Email</td>
            <td class="value">{{ $lease->tenant->email ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tenant Phone</td>
            <td class="value">{{ $lease->tenant->phone ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tenant Address</td>
            <td class="value">{{ $lease->tenant->address ?? '—' }}</td>
        </tr>
    </table>

    {{-- UNIT DETAILS --}}
    <h2>2. Storage Unit</h2>
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Facility</td>
            <td class="value">{{ $lease->unit->facility->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Unit Number</td>
            <td class="value">{{ $lease->unit->unit_number }}</td>
        </tr>
        <tr>
            <td class="label">Unit Size</td>
            <td class="value">{{ $lease->unit->size }}{{ $lease->unit->size_label ? ' (' . $lease->unit->size_label . ')' : '' }}</td>
        </tr>
    </table>

    {{-- LEASE TERMS --}}
    <h2>3. Lease Terms</h2>
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Move-In Date</td>
            <td class="value">{{ $lease->move_in_date->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Move-Out Date</td>
            <td class="value">{{ $lease->move_out_date ? $lease->move_out_date->format('F j, Y') : 'Month-to-month (no fixed end date)' }}</td>
        </tr>
        <tr>
            <td class="label">Monthly Rent</td>
            <td class="value">{{ \App\Models\Setting::money($lease->monthly_rate) }}</td>
        </tr>
        <tr>
            <td class="label">Billing Day</td>
            <td class="value">{{ ordinal($lease->billing_day) }} of each month</td>
        </tr>
        <tr>
            <td class="label">Late Fee Policy</td>
            <td class="value">
                @php $facility = $lease->unit->facility; @endphp
                @if ($facility)
                    {{ ucfirst($facility->late_fee_type ?? 'flat') }} fee of
                    {{ $facility->late_fee_type === 'percentage' ? $facility->late_fee_amount . '%' : \App\Models\Setting::money($facility->late_fee_amount ?? 0) }}
                    after {{ $facility->late_fee_grace_days ?? 0 }}-day grace period.
                @else
                    As per facility policy.
                @endif
            </td>
        </tr>
    </table>

    {{-- CLAUSES --}}
    <h2>4. Terms &amp; Conditions</h2>

    <div class="clause">
        <span class="clause-num">4.1</span>
        <strong>Payment.</strong> Tenant agrees to pay the monthly rent amount stated above on or before the billing day each month.
        Late payments are subject to late fees as outlined in the Late Fee Policy above.
    </div>
    <div class="clause">
        <span class="clause-num">4.2</span>
        <strong>Use of Unit.</strong> The storage unit shall be used solely for the storage of lawful personal or business property.
        Tenant shall not store hazardous, perishable, illegal, or flammable materials in the unit.
    </div>
    <div class="clause">
        <span class="clause-num">4.3</span>
        <strong>Access.</strong> Tenant shall have access to the storage unit during the facility's stated operating hours.
        The facility reserves the right to restrict access in the event of non-payment.
    </div>
    <div class="clause">
        <span class="clause-num">4.4</span>
        <strong>Termination.</strong> Either party may terminate this lease by providing written notice.
        Upon termination, the tenant must vacate the unit and remove all belongings. Tenant remains liable for rent through the move-out date.
    </div>
    <div class="clause">
        <span class="clause-num">4.5</span>
        <strong>Liability.</strong> The facility is not responsible for loss, damage, or theft of items stored in the unit.
        Tenant is encouraged to obtain appropriate insurance for stored goods.
    </div>
    <div class="clause">
        <span class="clause-num">4.6</span>
        <strong>Governing Law.</strong> This agreement shall be governed by applicable local laws and regulations.
    </div>

    @if ($lease->notes)
    <h2>5. Additional Notes</h2>
    <div class="clause">{{ $lease->notes }}</div>
    @endif

    {{-- SIGNATURES --}}
    <table class="sig-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="sig-line">
                    Tenant Signature &amp; Date<br>
                    <strong>{{ $lease->tenant->full_name }}</strong>
                </div>
            </td>
            <td>
                <div class="sig-line">
                    Authorised Signature &amp; Date<br>
                    <strong>{{ $company }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by {{ $company }} on {{ now()->format('F j, Y \a\t g:i A') }} — Agreement #{{ str_pad($lease->id, 6, '0', STR_PAD_LEFT) }}
    </div>

</div>
</body>
</html>
