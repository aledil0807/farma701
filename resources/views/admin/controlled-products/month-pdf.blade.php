<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte mensual de productos controlados</title>

    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 92px 45px 70px 45px;
        }

        body {
            margin: 0;
            font-family: arial, sans-serif;
            color: #000000;
            font-size: 12px;
        }

        .pdf-page {
            padding: 0;
        }

        .pdf-repeated-header {
            position: fixed;
            top: -76px;
            left: 0;
            right: 0;
            height: 68px;
        }

        .pdf-brand-table {
            width: auto;
            margin: 0 0 0 2px;
            border-collapse: collapse;
        }

        .pdf-brand-logo-cell {
            width: 54px;
            padding-right: 9px;
            vertical-align: middle;
        }

        .pdf-brand-logo-cell img {
            width: 48px;
            height: auto;
            max-height: 48px;
            object-fit: contain;
        }

        .pdf-brand-text-cell {
            vertical-align: middle;
            text-align: left;
        }

        .pdf-brand-text-cell h1 {
            margin: 0;
            font-size: 19px;
            line-height: 1.08;
            color: #000;
            font-weight: bold;
            letter-spacing: 0.03em;
        }

        .pdf-brand-text-cell p {
            margin: 2px 0 0;
            font-size: 12px;
            line-height: 1.15;
            color: #000;
        }

        .pdf-divider {
            margin: 6px 0 0;
            border-top: 1.2px solid #000;
        }

        .pdf-category-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pdf-category-section + .pdf-category-section {
            padding-top: 16px;
            border-top: 1.2px solid #000;
        }

        .pdf-products-table tr,
        .pdf-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
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

        .pdf-official-meta__date {
            width: 190px;
            text-align: right;
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
            padding: 16px;
            text-align: center;
            color: #000;
            border: 1px solid #000;
            background: #fff;
            font-size: 10px;
        }

        .pdf-signature-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -54px;
            text-align: center;
            font-size: 16px;
            line-height: 1.25;
            color: #000;
        }
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $reportDate = now()->format('d/m/Y');

        $movementMonth = $month
            ? Carbon::createFromFormat('Y-m', $month)->locale('es')->translatedFormat('F Y')
            : now()->subMonth()->locale('es')->translatedFormat('F Y');

        $movementMonth = mb_strtoupper($movementMonth);

        $logoPath = null;

        $possibleLogoPaths = [
            base_path('../public_html/assets/img/Logo.png'),
            base_path('../public_html/asset/img/Logo.png'),
            public_path('assets/img/Logo.png'),
            public_path('asset/img/Logo.png'),
        ];

        foreach ($possibleLogoPaths as $possibleLogoPath) {
            if (file_exists($possibleLogoPath) && is_readable($possibleLogoPath)) {
                $logoPath = realpath($possibleLogoPath);
                break;
            }
        }

        $logoDataUri = null;

        if ($logoPath) {
            $mimeType = mime_content_type($logoPath) ?: 'image/png';

            $logoDataUri = 'data:' . $mimeType . ';base64,' . base64_encode(
                file_get_contents($logoPath)
            );
        }
    @endphp

    <div class="pdf-repeated-header">
        <table class="pdf-brand-table">
            <tr>
                <td class="pdf-brand-logo-cell">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Farmacia 701">
                    @endif
                </td>

                <td class="pdf-brand-text-cell">
                    <h1>FARMACIA 701, C.A.</h1>
                    <p>RIF: J-30831331-9</p>
                    <p>Av. 17 de diciembre C/C Calle Madrid, Local # 28, Ciudad Bolívar</p>
                </td>
            </tr>
        </table>

        <div class="pdf-divider"></div>
    </div>

    <div class="pdf-signature-footer">
        <strong>Dra. Tania Biutti<br>
        C.I.: 5.553.831 M.P.P.S.: 6615<br>
        COLFAR: 466<br>
        INPREFAR: 061245</strong>
    </div>

    <div class="pdf-page">
        @foreach($categories as $category)
            @php
                $report = $reportsByCategory[$category] ?? null;
                $items = $report ? $report->items : collect();

                $categoryMovementTexts = [
                    'Psicotrópicos' => 'Movimiento de las Existencias de Sustancias Psicotrópicas de Récipe Corriente (Art. 27 y 28 Ley Orgánica Sobre Sustancias Estupefacientes y Psicotrópicas) durante el mes:',
                    'Estupefacientes' => 'Movimiento de las Existencias de Sustancias Estupefacientes de Récipe Violeta (Art. 27 y 28 Ley Orgánica Sobre Sustancias Estupefacientes y Psicotrópicas) durante el mes:',
                    'Codeínas y sus sales' => 'Movimiento de las Existencias de Codeínas y sus Sales durante el mes:',
                    'Misoprostol' => 'Movimiento de las Existencias de Misoprostol durante el mes:',
                    'Oxazepam' => 'Movimiento de las Existencias de Oxazepam durante el mes:',
                    'Morfina' => 'Movimiento de las Existencias de Morfina durante el mes:',
                    'Fentanilo' => 'Movimiento de las Existencias de Fentanilo durante el mes:',
                ];

                $movementText = $categoryMovementTexts[$category] ?? 'Movimiento de las Existencias de Productos Controlados durante el mes:';
            @endphp

            <section class="pdf-category-section">
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
                    {{ $movementText }}
                    <strong>{{ $movementMonth }}</strong>
                </p>

                @if($items->isEmpty())
                    <div class="pdf-empty">
                        No hay productos registrados para esta categoría.
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
                            @foreach($items as $item)
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
            </section>
        @endforeach
    </div>
</body>

</html>