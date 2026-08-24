@php
    $company = \App\Models\Setting::get('company_name', 'StorageCRM');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone = \App\Models\Setting::get('company_phone', '');
    $companyEmail = \App\Models\Setting::get('company_email', '');
    $trn = \App\Models\Setting::get('trn_number');

    $employee = $salaryRecord->employee;
    $slipNumber = 'SAL-' . str_pad($salaryRecord->id, 6, '0', STR_PAD_LEFT);

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
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Slip {{ $slipNumber }}</title>
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

        .page {
            padding: 0 0 40px 0;
        }

        /* ── Header ── */
        .header-wrap {
            background: #fa7e11;
            padding: 28px 32px 24px;
        }

        .header-table {
            width: 100%;
        }

        .brand-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
            line-height: 1.7;
        }

        .slip-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.7);
        }

        .slip-number {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            margin-top: 3px;
        }

        .slip-period {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .badge-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        /* ── Body ── */
        .body-pad {
            padding: 28px 32px 0;
        }

        /* ── Employee card ── */
        .emp-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px 22px;
            margin-bottom: 24px;
        }

        .emp-table {
            width: 100%;
            border-collapse: collapse;
        }

        .emp-table td {
            padding: 4px 10px 4px 0;
            font-size: 12px;
            vertical-align: top;
        }

        .emp-label {
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            width: 130px;
        }

        .emp-value {
            color: #1e293b;
            font-weight: 500;
        }

        /* ── Earnings/Deductions table ── */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 8px;
            margin-top: 22px;
        }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
        }

        .breakdown-table thead th {
            background: #f1f5f9;
            padding: 9px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }

        .breakdown-table thead th.right {
            text-align: right;
        }

        .breakdown-table tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .breakdown-table tbody td.right {
            text-align: right;
            font-weight: 600;
        }

        .breakdown-table tbody td.green {
            color: #16a34a;
            font-weight: 700;
        }

        .breakdown-table tbody td.red {
            color: #dc2626;
            font-weight: 700;
        }

        /* ── Net pay summary ── */
        .net-wrap {
            margin-top: 20px;
        }

        .net-inner {
            width: 280px;
            float: right;
        }

        .net-table {
            width: 100%;
            border-collapse: collapse;
        }

        .net-table td {
            padding: 6px 0;
            font-size: 13px;
        }

        .net-table td.label {
            color: #64748b;
        }

        .net-table td.amount {
            text-align: right;
            font-weight: 700;
        }

        .net-divider td {
            border-top: 2px solid #1e293b;
            padding-top: 0;
        }

        .net-total td {
            padding-top: 10px;
            font-size: 19px;
            font-weight: 700;
            color: #fa7e11;
        }

        .clearfix {
            clear: both;
        }

        /* ── Notes ── */
        .notes-box {
            margin-top: 28px;
            padding: 16px 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .notes-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        /* ── Signature block ── */
        .sig-wrap {
            margin-top: 40px;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-table td {
            vertical-align: bottom;
            padding: 0 10px;
            width: 50%;
        }

        .sig-table td:first-child {
            padding-left: 0;
        }

        .sig-table td:last-child {
            padding-right: 0;
        }

        .sig-line {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 11px;
            color: #64748b;
            margin-top: 48px;
        }

        .sig-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            padding: 24px 32px 0;
            border-top: 1px solid #e2e8f0;
            margin-top: 32px;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- HEADER --}}
        <div class="header-wrap">
            <table class="header-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:middle; width:55%;">
                        @if ($logoData)
                            <img src="{{ $logoData }}" style="height:85px; max-width:260px; object-fit:contain;">
                        @else
                            <div style="font-size:24px; font-weight:700; color:#fff;">{{ $company }}</div>
                        @endif
                        <div class="brand-sub">
                            @if ($trn) TRN: {{ $trn }}<br> @endif
                            @if ($companyAddress){{ $companyAddress }}<br>@endif
                            @if ($companyPhone){{ $companyPhone }}@if($companyEmail) &nbsp;·&nbsp;
                            {{ $companyEmail }}@endif@endif
                        </div>
                    </td>
                    <td style="vertical-align:top; text-align:right;">
                        <div class="slip-label">Salary Slip</div>
                        <div class="slip-number">{{ $slipNumber }}</div>
                        <div class="slip-period">{{ $salaryRecord->month_label }}</div>
                        @if ($salaryRecord->paid_at)
                            <div style="font-size:11px; color:rgba(255,255,255,0.75); margin-top:4px;">
                                Paid: {{ $salaryRecord->paid_at->format('d M Y') }}
                            </div>
                        @endif
                        <span
                            class="badge badge-{{ $salaryRecord->status }}">{{ ucfirst($salaryRecord->status) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="body-pad">

            {{-- EMPLOYEE DETAILS --}}
            <div class="section-title" style="margin-top:24px;">Employee Information</div>
            <div class="emp-card">
                <table class="emp-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="emp-label">Full Name</td>
                        <td class="emp-value">{{ $employee->full_name }}</td>
                        <td class="emp-label">Position</td>
                        <td class="emp-value">{{ $employee->position ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="emp-label">Department</td>
                        <td class="emp-value">{{ $employee->department ?? '—' }}</td>
                        <td class="emp-label">Emirates ID</td>
                        <td class="emp-value">{{ $employee->emirates_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="emp-label">Email</td>
                        <td class="emp-value">{{ $employee->email ?? '—' }}</td>
                        <td class="emp-label">Phone</td>
                        <td class="emp-value">{{ $employee->phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="emp-label">Join Date</td>
                        <td class="emp-value">{{ $employee->join_date?->format('d M Y') ?? '—' }}</td>
                        <td class="emp-label">Pay Period</td>
                        <td class="emp-value" style="font-weight:700; color:#fa7e11;">{{ $salaryRecord->month_label }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- EARNINGS & DEDUCTIONS --}}
            <div class="section-title">Earnings &amp; Deductions</div>
            <table class="breakdown-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="right" style="width:180px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Base Salary</td>
                        <td class="right">{{ \App\Models\Setting::money($salaryRecord->base_salary) }}</td>
                    </tr>
                    @if ((float) $salaryRecord->bonuses > 0)
                        <tr>
                            <td>Bonuses / Allowances</td>
                            <td class="right green">+ {{ \App\Models\Setting::money($salaryRecord->bonuses) }}</td>
                        </tr>
                    @endif
                    @if ((float) $salaryRecord->deductions > 0)
                        <tr>
                            <td>Deductions</td>
                            <td class="right red">- {{ \App\Models\Setting::money($salaryRecord->deductions) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- NET PAY --}}
            <div class="net-wrap">
                <div class="net-inner">
                    <table class="net-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">Gross Earnings</td>
                            <td class="amount">
                                {{ \App\Models\Setting::money((float) $salaryRecord->base_salary + (float) $salaryRecord->bonuses) }}
                            </td>
                        </tr>
                        @if ((float) $salaryRecord->deductions > 0)
                            <tr>
                                <td class="label">Total Deductions</td>
                                <td class="amount" style="color:#dc2626;">-
                                    {{ \App\Models\Setting::money($salaryRecord->deductions) }}</td>
                            </tr>
                        @endif
                        <tr class="net-divider">
                            <td colspan="2" style="padding:0;"></td>
                        </tr>
                        <tr class="net-total">
                            <td>Net Pay</td>
                            <td style="text-align:right;">{{ \App\Models\Setting::money($salaryRecord->total) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="clearfix"></div>

            @if ($salaryRecord->notes)
                <div class="notes-box">
                    <div class="notes-label">Notes</div>
                    <p style="font-size:12px; color:#475569; margin-top:4px;">{{ $salaryRecord->notes }}</p>
                </div>
            @endif

            {{-- SIGNATURE BLOCK --}}
            <div class="sig-wrap">
                <table class="sig-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            @if ($stampData || $signatureData)
                                <div style="height:90px; margin-bottom:5px;">
                                    @if ($signatureData)
                                        <img src="{{ $signatureData }}"
                                            style="max-height:60px; max-width:150px; margin-bottom:-30px; margin-right:-15px; position:relative; z-index:10;">
                                    @endif
                                    @if ($stampData)
                                        <img src="{{ $stampData }}" style="max-height:85px; max-width:85px; opacity:0.85;">
                                    @endif
                                </div>
                            @endif
                            <div class="sig-line">{{ $company }}</div>
                            <div class="sig-label">Authorized Signature</div>
                        </td>
                        <td style="text-align:right;">
                            <div class="sig-line" style="margin-top:48px;">{{ $employee->full_name }}</div>
                            <div class="sig-label">Employee Acknowledgement</div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>{{-- /body-pad --}}

        <div class="footer">
            <p>This is a computer generated salary slip and does not require a physical signature.</p>
            <p style="margin-top:4px;">Generated by {{ $company }} on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

    </div>
</body>

</html>