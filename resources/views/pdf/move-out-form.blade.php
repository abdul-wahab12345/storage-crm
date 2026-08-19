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

    $formIdStr = str_pad($form->id, 6, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Move Out Form — {{ $tenant->full_name }}</title>
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
            font-size: 13px;
            line-height: 1.6;
        }

        /* ── Header ── */
        .header-wrap {
            background: #EA580C;
            padding: 24px 40px;
            margin-bottom: 28px;
        }

        .header-table {
            width: 100%;
        }

        .brand-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.82);
            margin-top: 4px;
            line-height: 1.4;
        }

        .doc-title {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            text-align: right;
            letter-spacing: -0.3px;
        }

        .doc-sub {
            font-size: 10.5px;
            color: rgba(255, 255, 255, 0.7);
            text-align: right;
            margin-top: 3px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── Body ── */
        .body {
            padding: 0 40px 30px;
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
            font-size: 12.5px;
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
            margin-bottom: 20px;
        }

        .two-info td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }

        .col-r {
            padding-left: 20px;
            border-left: 1px solid #f1f5f9;
        }

        /* ── Paragraph Terms ── */
        .terms-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 16px 20px;
            margin: 30px 0;
            font-size: 13px;
            color: #334155;
            line-height: 1.7;
            text-align: justify;
        }

        .terms-box strong {
            color: #0f172a;
        }

        /* ── Signatures ── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
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
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .sig-name {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 45px;
        }

        .sig-line {
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 10.5px;
            color: #64748b;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header-wrap">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle; width:55%;">
                    @if ($logoData)
                        <img src="{{ $logoData }}" style="height:90px; max-width:280px; object-fit:contain;">
                    @else
                        <div style="font-size:24px; font-weight:700; color:#fff;">{{ $company }}</div>
                    @endif
                    <div class="brand-sub">
                        @if ($companyAddress){{ $companyAddress }}<br>@endif
                        @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;·&nbsp;
                        {{ $companyEmail }}@endif@endif
                    </div>
                </td>
                <td style="vertical-align:top; text-align:right;">
                    <div class="doc-title">Move Out Form</div>
                    <div class="doc-sub">Form No: MO-{{ $formIdStr }}</div>
                    <div class="doc-sub" style="margin-top:4px;">Date: {{ $form->created_at->format('d M Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">

        {{-- CUSTOMER DETAILS --}}
        <h2>Form Details</h2>
        <table class="two-info" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <table class="info-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Customer Name</td>
                            <td class="value">{{ $tenant->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mobile Number</td>
                            <td class="value">{{ $tenant->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Email</td>
                            <td class="value">{{ $tenant->email ?? '—' }}</td>
                        </tr>
                    </table>
                </td>
                <td class="col-r">
                    <table class="info-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Move In Date</td>
                            <td class="value">{{ $lease->move_in_date ? $lease->move_in_date->format('d M Y') : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Move Out Date</td>
                            <td class="value" style="font-weight:700; color:#EA580C;">
                                {{ $form->move_out_date ? $form->move_out_date->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Unit Number</td>
                            <td class="value">{{ $unit->unit_number ?? '—' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- TERMS PARAGRAPH --}}
        <h2>Terms & Declaration</h2>
        <div class="terms-box">
            <strong>Dear Customer,</strong><br><br>
            As your storage rental period has ended, you are collecting your goods from {{ $company }} in your own
            presence and at your own risk. Please inspect all items at the time of collection. Any complaint must be
            reported before the goods leave the storage facility.
            <br><br>
            Upon collection of your goods, your storage contract is terminated, and {{ $company }} shall not be
            responsible for any loss, damage, missing items, or claims thereafter. Thank you for your cooperation.
        </div>

        {{-- SIGNATURES --}}
        <table class="sig-table" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="sig-label">Customer Signature</div>
                    <div class="sig-name">{{ $tenant->full_name }}</div>
                    <div class="sig-line">Signature
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        Date: _______________</div>
                </td>
                <td class="right-sig">
                    <div class="sig-label">Authorised Signature &amp; Stamp — {{ $company }}</div>
                    <table cellpadding="0" cellspacing="0" style="margin-top:8px;">
                        <tr>
                            <td style="vertical-align:bottom; padding-right:16px; height: 60px;">
                                @if ($signatureData)
                                    <img src="{{ $signatureData }}"
                                        style="max-height:55px; max-width:140px; display:block;">
                                @endif
                            </td>
                            <td style="vertical-align:bottom; height: 60px;">
                                @if ($stampData)
                                    <img src="{{ $stampData }}"
                                        style="max-height:75px; max-width:75px; display:block; opacity:0.88; margin-bottom: -10px;">
                                @endif
                            </td>
                        </tr>
                    </table>
                    <div class="sig-line" style="margin-top: 15px;">Signature
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        Date: {{ $form->created_at->format('d M Y') }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated by {{ $company }} &nbsp;·&nbsp; Form #MO-{{ $formIdStr }}
        </div>

    </div>
</body>

</html>