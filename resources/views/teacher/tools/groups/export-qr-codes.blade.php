<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>group-{{ $groupUuid }}-qr-codes</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }

        .card-wrapper {
            width: 49%;
            display: inline-block;
            vertical-align: top;
            margin-bottom: 5mm;
            box-sizing: border-box;
            page-break-inside: avoid !important;
        }

        .qr-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            background: #fff;
            padding: 3mm 5mm;
            box-sizing: border-box;
        }

        .top-section-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-section-table .qr-td {
            width: 30mm;
            vertical-align: middle;
        }

        .top-section-table .qr-td img.qr-code {
            width: 25mm;
            height: 25mm;
            display: block;
        }

        .top-section-table .student-td {
            vertical-align: middle;
            text-align: right;
            padding-right: 3mm;
        }

        .student-td .student-name {
            font-weight: bold;
            font-size: 12pt;
            color: #333;
            margin: 0;
        }

        .student-td .student-id {
            font-size: 9pt;
            color: #666;
            margin: 1mm 0 0 0;
        }

        .separator {
            border: 0;
            border-top: 1px solid #f0f0f0;
            margin: 4mm 0;
        }

        .footer-container {
            width: 100%;
            overflow: auto;
        }

        .footer-container::after {
            content: ".";
            visibility: hidden;
            display: block;
            height: 0;
            clear: both;
        }

        .footer-right {
            float: right;
            width: 50%;
            text-align: right;
            direction: rtl;
        }

        .footer-left {
            float: left;
            width: 50%;
            text-align: left;
            direction: ltr;
            font-size: 8pt;
            color: #777;
        }

        .footer-right img.platform-logo {
            width: 5mm;
            height: 5mm;
            margin-left: 1mm;
            vertical-align: middle;
        }

        .footer-right .platform-name-small {
            font-size: 8pt;
            font-weight: bold;
            color: #444;
            vertical-align: middle;
        }

        .footer-right img.platform-logo,
        .footer-right .platform-name-small {
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    @php
        $platformPhoneNumber = '01098617164';
    @endphp

    @foreach ($qrCodes as $item)
        <div class="card-wrapper">
            <div class="qr-card">

                <table class="top-section-table">
                    <tr>
                        <td class="qr-td">
                            <img src="{{ $item['qr_code'] }}" alt="QR Code" class="qr-code">
                        </td>
                        <td class="student-td">
                            <p class="student-name">{{ $item['student_name'] }}</p>
                            <p class="student-id">الأيدي : {{ $item['student_id'] }}</p>
                        </td>
                    </tr>
                </table>

                <hr class="separator">

                <div class="footer-container">
                    <div class="footer-right">
                        @if (file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Shattor" class="platform-logo">
                        @endif
                        <span class="platform-name-small">{{ $platformName }}</span>
                    </div>

                    <div class="footer-left">
                        @if (!empty($platformPhoneNumber))
                            {{ $platformPhoneNumber }}
                        @endif
                    </div>
                </div>

            </div>
        </div>
    @endforeach
</body>

</html>
