<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de productos controlados</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: arial, sans-serif;
            color: #000000;
            font-size: 12px;
        }

        .pdf-page {
            padding: 22px;
        }

        .pdf-header {
            margin-bottom: 18px;

            padding-bottom: 12px;
        }

        .pdf-title {
            margin: 0 0 6px;
            font-size: 20px;
            color: #000;
            text-transform: uppercase;
        }

        .pdf-meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .pdf-header {
            margin-bottom: 14px;
        }

        .pdf-brand-table {
            width: auto;
            margin: 0 0 0 6px;
            border-collapse: collapse;
        }

        .pdf-brand-logo-cell {
            width: 42px;
            padding-right: 8px;
            vertical-align: middle;
        }

        .pdf-brand-logo-cell img {
            width: 38px;
            height: auto;
            max-height: 38px;
            object-fit: contain;
        }

        .pdf-brand-text-cell {
            vertical-align: middle;
            text-align: left;
        }

        .pdf-brand-text-cell h1 {
            margin: 0;
            font-size: 13px;
            line-height: 1.1;
            color: #000;
            font-weight: bold;
            letter-spacing: 0.03em;
        }

        .pdf-brand-text-cell p {
            margin: 3px 0 0;
            font-size: 9px;
            line-height: 1;
            color: #22313f;
        }

        .pdf-divider {
            margin: 8px 0 10px;
            border-top: 1.2px solid #22313f;
        }

        .pdf-official-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .pdf-official-meta td {
            padding: 2px 0;
            font-size: 12px;
            color: #000;
            vertical-align: top;
        }

        .pdf-normal-text {
            margin: 4px 0;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
        }

        .pdf-legal-text {
            margin: 6px 0 9px;
            font-size: 12px;
            line-height: 1.38;
            text-align: justify;
            color: #000;
        }

        .pdf-official-meta__date {
            width: 190px;
            text-align: right;
        }



        .pdf-report-extra {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
            margin-bottom: 10px;
        }

        .pdf-report-extra td {
            padding: 5px 8px;
            font-size: 9.5px;
            border: 1px solid #d8e4ef;
            background: #f7fbff;
        }

        .pdf-meta td {
            padding: 4px 0;
            font-size: 11px;
        }

        .pdf-meta strong {
            color: #22313f;
        }

        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .pdf-table th {
            background: #ffffff;
            color: #000;
            padding: 8px 6px;
            border: 1px solid #000;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
            font-weight: bold;
        }

        .pdf-table td {
            padding: 8px 6px;
            border: 1px solid #000;
            vertical-align: top;
            font-size: 10px;
        }

        .pdf-table tr:nth-child(even) td {
            background: #fff;
        }

        .col-product {
            width: 25%;
        }

        .col-drugstore {
            width: 13%;
        }

        .col-invoice {
            width: 10%;
        }

        .col-number {
            width: 8%;
            text-align: center;
        }

        .pdf-empty {
            padding: 22px;
            text-align: center;
            color: #667685;
            border: 1px solid #d8e4ef;
            background: #f7fbff;
        }

        .pdf-footer {
            margin-top: 16px;
            font-size: 9px;
            color: #667685;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $reportDate = now()->format('d/m/Y');

        $movementMonth = $report->report_month
            ? Carbon::createFromFormat('Y-m', $report->report_month)->locale('es')->translatedFormat('F Y')
            : now()->subMonth()->locale('es')->translatedFormat('F Y');

        $logoPath = public_path('assets/img/Logo.png');
    @endphp
    <div class="pdf-page">
        <header class="pdf-header">
            <table class="pdf-brand-table">
                <tr>
                    <td class="pdf-brand-logo-cell">
                        @if(file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Farmacia 701">
                        @endif
                    </td>

                    <td class="pdf-brand-text-cell">
                        <h1>FARMACIA 701, C.A.</h1>
                        <p>Rif: J-30831331-9</p>
                    </td>
                </tr>
            </table>

            <div class="pdf-divider"></div>

            <table class="pdf-official-meta">
                <tr>
                    <td>
                        <strong>Establecimiento farmacéutico:</strong>
                        FARMACIA 701, C.A.
                    </td>

                    <td class="pdf-official-meta__date">
                        <strong>Fecha:</strong>
                        {{ $reportDate }}
                    </td>
                </tr>
            </table>

            <p class="pdf-normal-text">
                Con el permiso de funcionamiento registrado bajo el N° 194-BOL
            </p>

            <p class="pdf-legal-text">
                Movimiento de las existencias de
                <strong>{{ $report->category ?: 'productos controlados' }}</strong>
                durante el mes:
                <strong>{{ $movementMonth }}</strong>
            </p>


        </header>

        @if($report->items->isEmpty())
            <div class="pdf-empty">
                Este reporte no tiene productos registrados.
            </div>
        @else
            <table class="pdf-table">
                <thead>
                    <tr>
                        <th class="col-product">Producto</th>
                        <th class="col-drugstore">Droguería</th>
                        <th class="col-invoice">Factura</th>
                        <th class="col-number">Exist. anterior</th>
                        <th class="col-number">Entradas</th>
                        <th class="col-number">Salidas</th>
                        <th class="col-number">Exist. actual</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($report->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->drugstore ?: '—' }}</td>
                            <td>{{ $item->invoice_number ?: '—' }}</td>
                            <td class="col-number">{{ $item->previous_stock }}</td>
                            <td class="col-number">{{ $item->entries }}</td>
                            <td class="col-number">{{ $item->exits }}</td>
                            <td class="col-number">
                                <strong>{{ $item->current_stock }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>

</html>