@php
    $company = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone = \App\Models\Setting::get('company_phone', '');
    $companyEmail = \App\Models\Setting::get('company_email', '');

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

    $facility = $lease->unit->facility ?? null;
    $agreementNo = str_pad($lease->id, 6, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Storage Agreement — {{ $lease->tenant->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif;
        }

        body {
            color: #1e293b;
            background: #fff;
            font-size: 12.5px;
            line-height: 1.6;
        }

        /* ── Header ── */
        .header-wrap {
            background: #EA580C;
            padding: 16px 30px;
        }

        .header-table {
            width: 100%;
        }

        .brand-sub {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.82);
            margin-top: 4px;
            line-height: 1.4;
        }

        .doc-title {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            text-align: right;
            letter-spacing: -0.3px;
        }

        .doc-sub {
            font-size: 9.5px;
            color: rgba(255, 255, 255, 0.7);
            text-align: right;
            margin-top: 3px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── Body ── */
        .body {
            padding: 25px 35px 30px;
        }

        /* ── Agreement number banner ── */
        .agr-banner {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 5px;
            padding: 8px 16px;
            margin-bottom: 24px;
        }

        .agr-banner-table {
            width: 100%;
        }

        .agr-no {
            font-size: 11px;
            font-weight: 700;
            color: #EA580C;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .agr-date {
            font-size: 11px;
            color: #92400e;
            text-align: right;
        }

        /* ── Section headers ── */
        h2 {
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: #EA580C;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 10px 4px;
            margin: 18px 0 10px;
            border-radius: 3px;
            line-height: 1.2;
        }

        /* ── Info table ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .info-table td {
            padding: 7px 8px;
            font-size: 12px;
            vertical-align: top;
            border-bottom: 1px solid #f8fafc;
        }

        .info-table td.label {
            color: #64748b;
            width: 36%;
            font-weight: 700;
        }

        .info-table td.value {
            color: #1e293b;
            font-weight: 500;
        }

        /* ── Two-column info ── */
        .two-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .two-info td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }

        .col-r {
            padding-left: 16px;
            border-left: 1px solid #f1f5f9;
        }

        /* ── Prohibited items box ── */
        .warn-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 14px 0 16px;
            font-size: 11.5px;
            color: #7f1d1d;
        }

        .warn-box strong {
            color: #991b1b;
        }

        /* ── Access hours box ── */
        .access-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 14px 0 16px;
            font-size: 11.5px;
            color: #14532d;
        }

        /* ── Conditions of agreement ── */
        .conditions-wrap {
            margin-top: 6px;
        }

        .cond-item {
            margin-bottom: 14px;
            font-size: 11.5px;
            line-height: 1.55;
        }

        .cond-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1px;
        }

        .cond-sub {
            padding-left: 14px;
            color: #374151;
        }

        /* ── Acknowledgement ── */
        .ack-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 14px 16px;
            margin: 20px 0;
            font-size: 12px;
            font-style: italic;
            color: #475569;
            text-align: center;
        }

        /* ── Signatures ── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .sig-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 20px 0 0;
        }

        .sig-table td.right-sig {
            padding: 0 0 0 20px;
            border-left: 1px solid #f1f5f9;
        }

        .sig-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .sig-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 35px;
        }

        .sig-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-size: 9.5px;
            color: #94a3b8;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>

<body>

    {{-- ORANGE HEADER --}}
    <div class="header-wrap">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle; width:55%;">
                    @if ($logoData)
                        <img src="{{ $logoData }}" style="height:90px; max-width:280px; object-fit:contain;">
                    @else
                        <div style="font-size:22px; font-weight:700; color:#fff;">{{ $company }}</div>
                    @endif
                    <div class="brand-sub">
                        @if ($companyAddress){{ $companyAddress }}<br>@endif
                        @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;·&nbsp;
                        {{ $companyEmail }}@endif@endif
                    </div>
                </td>
                <td style="vertical-align:top; text-align:right;">
                    <div class="doc-title">Storage Agreement</div>
                    <div class="doc-sub">Agreement No: {{ $agreementNo }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">

        {{-- AGREEMENT NUMBER BANNER --}}
        <div class="agr-banner">
            <table class="agr-banner-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="agr-no">Agreement No: {{ $agreementNo }}</td>
                    <td class="agr-date">Date: {{ $lease->move_in_date->format('d / m / Y') }}</td>
                </tr>
            </table>
        </div>

        {{-- CUSTOMER DETAILS --}}
        <h2>Customer Details</h2>
        <table class="two-info" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <table class="info-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Full Name</td>
                            <td class="value">{{ $lease->tenant->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Email</td>
                            <td class="value">{{ $lease->tenant->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Phone</td>
                            <td class="value">{{ $lease->tenant->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Address</td>
                            <td class="value">{{ $lease->tenant->address ?? '—' }}</td>
                        </tr>
                        @if($lease->storage_type)
                            <tr>
                                <td class="label">Storage Type</td>
                                <td class="value">{{ ucfirst($lease->storage_type) }}</td>
                            </tr>
                        @endif
                        @if($lease->goods_condition)
                            <tr>
                                <td class="label">Goods Condition</td>
                                <td class="value">{{ ucfirst($lease->goods_condition) }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
                <td class="col-r">
                    <table class="info-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Facility</td>
                            <td class="value">{{ $facility?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Unit No</td>
                            <td class="value">{{ $lease->unit->unit_number }}</td>
                        </tr>
                        @if($lease->space_details)
                            <tr>
                                <td class="label">Space Details</td>
                                <td class="value">{{ $lease->space_details }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="label">Unit Size</td>
                            <td class="value">
                                {{ $lease->unit->size }}{{ $lease->unit->size_label ? ' (' . $lease->unit->size_label . ')' : '' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- AGREEMENT TERMS --}}
        <h2>Agreement Terms</h2>
        <table class="two-info" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <table class="info-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Move-In Date</td>
                            <td class="value">{{ $lease->move_in_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Move-Out Date</td>
                            <td class="value">
                                {{ $lease->move_out_date ? $lease->move_out_date->format('d M Y') : 'Month-to-month' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Monthly Rate</td>
                            <td class="value" style="font-weight:700; color:#EA580C;">
                                {{ \App\Models\Setting::money($lease->monthly_rate) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Billing Day</td>
                            <td class="value">{{ ordinal($lease->billing_day) }} of each month</td>
                        </tr>
                    </table>
                </td>
                <td class="col-r">
                    <table class="info-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Payment Terms</td>
                            <td class="value">Monthly (in advance)</td>
                        </tr>
                        <tr>
                            <td class="label">Late Fee</td>
                            <td class="value">
                                @if ($facility)
                                    {{ ucfirst($facility->late_fee_type ?? 'flat') }}
                                    {{ $facility->late_fee_type === 'percentage' ? $facility->late_fee_amount . '%' : \App\Models\Setting::money($facility->late_fee_amount ?? 0) }}
                                    after {{ $facility->late_fee_grace_days ?? 30 }}-day grace
                                @else
                                    30% after 30-day grace period
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Notice Period</td>
                            <td class="value">1 month prior written notice</td>
                        </tr>
                        <tr>
                            <td class="label">Payment Methods</td>
                            <td class="value">Cash / Cheque / Bank Card</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- STORAGE COSTS --}}
        <h2>Storage Costs</h2>
        <p style="font-size:11px; color:#374151; margin-bottom:8px;">
            All payments are to be made <strong>in advance</strong> by the customer at the time of agreement.
            The storage fee is payable directly to the management on time and in full throughout the storage period.
            A <strong>30% late payment penalty</strong> is applicable on unpaid dues after 30 days.
            If payment is not received within <strong>45 days</strong>, Delight Box Storage has the full right to
            dispose of stored items
            without any liability towards the customer.
        </p>

        {{-- STORAGE SPACE ACCESS --}}
        <h2>Storage Space Access</h2>
        <div class="access-box">
            <strong>Business Hours:</strong> Monday – Saturday, 9:00 AM to 6:00 PM<br>
            To access units during off-hours or on Sundays, the customer must notify management <strong>1 day in
                advance</strong>.
        </div>

        {{-- PROHIBITED ITEMS --}}
        <div class="warn-box">
            <strong>Prohibited Items:</strong> Customers must NOT store flammable, dangerous, illegal, stolen,
            perishable,
            environmentally harmful, or explosive goods, currency, jewellery, or items of personal sentiment.
            The storage space is only accessible during set access hours as determined by warehouse management.
        </div>

        {{-- CONDITIONS OF AGREEMENT --}}
        <h2>Conditions of Agreement</h2>
        <div class="conditions-wrap" style="font-size: 11.5px; line-height: 1.55; text-align: justify; color: #374151;">
            {!! $lease->custom_terms ?: \App\Models\Setting::get('agreement_terms_conditions', '<p>No terms configured.</p>') !!}
        </div>

        @if ($lease->notes)
            <h2>Additional Notes</h2>
            <p style="font-size:11px; color:#374151; padding: 8px 0;">{{ $lease->notes }}</p>
        @endif

        {{-- ACKNOWLEDGEMENT --}}
        <div class="ack-box">
            I have read all the terms and conditions of this agreement and agree to follow them.
        </div>

        {{-- SIGNATURES --}}
        <table class="sig-table" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="sig-label">Customer Signature</div>
                    <div class="sig-name">{{ $lease->tenant->full_name }}</div>
                    <div class="sig-line">Signature
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        Date: _______________</div>
                </td>
                <td class="right-sig">
                    <div class="sig-label">Authorised Signature &amp; Stamp — {{ $company }}</div>
                    <table cellpadding="0" cellspacing="0" style="margin-top:8px;">
                        <tr>
                            <td style="vertical-align:bottom; padding-right:16px;">
                                @if ($signatureData)
                                    <img src="{{ $signatureData }}" style="height:50px; max-width:140px; display:block;">
                                @endif
                            </td>
                            <td style="vertical-align:bottom;">
                                @if ($stampData)
                                    <img src="{{ $stampData }}"
                                        style="height:72px; max-width:72px; display:block; opacity:0.88;">
                                @endif
                            </td>
                        </tr>
                    </table>
                    <div class="sig-line" style="margin-top: 8px;">Signature
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        Date: {{ now()->format('d M Y') }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated by {{ $company }} &nbsp;·&nbsp; {{ now()->format('F j, Y \a\t g:i A') }} &nbsp;·&nbsp; Agreement
            #{{ $agreementNo }}
        </div>

    </div>
</body>

</html>