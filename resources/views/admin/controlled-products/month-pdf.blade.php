<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte mensual de productos controlados</title>

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

        .pdf-section {
            margin-bottom: 28px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pdf-section+.pdf-section {
            padding-top: 16px;
            border-top: 1.2px solid #000;
        }

        .pdf-header {
            margin-bottom: 14px;
            padding-bottom: 10px;
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
            color: #000;
        }

        .pdf-divider {
            margin: 8px 0 10px;
            border-top: 1.2px solid #000;
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
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $reportDate = now()->format('d/m/Y');

        $movementMonth = $month
            ? Carbon::createFromFormat('Y-m', $month)->locale('es')->translatedFormat('F Y')
            : now()->subMonth()->locale('es')->translatedFormat('F Y');

        $logoPath = public_path('assets/img/Logo.png');

        $categoryMovementTexts = [
            'Psicotrópicos' => 'Movimiento de las Existencias de Sustancias Psicotrópicas de Récipe Corriente (Art. 27 y 28 Ley Orgánica Sobre Sustancias Estupefacientes y Psicotrópicas) durante el mes:',

            'Estupefacientes' => 'Movimiento de las Existencias de Sustancias Estupefacientes de Récipe Violeta (Art. 27 y 28 Ley Orgánica Sobre Sustancias Estupefacientes y Psicotrópicas) durante el mes:',

            'Codeínas y sus sales' => 'Relación de Codeínas y sus Sales durante el mes:',

            'Misoprostol' => 'Relación de Misoprostol durante el mes:',

            'Oxazepam' => 'Relación de Oxazepam durante el mes:',

            'Morfina' => 'Relación de Morfina durante el mes:',

            'Fentanilo' => 'Relación de Fentanilo durante el mes:',
        ];
    @endphp

    <div class="pdf-page">
        @foreach($categories as $category)
            @php
                $report = $reportsByCategory->get($category);
                $items = $report ? $report->items : collect();
            @endphp

            <section class="pdf-section">
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

                    @php
                        $movementText = $categoryMovementTexts[$category] ?? 'Movimiento de las Existencias de Productos Controlados durante el mes:';
                    @endphp

                    <p class="pdf-legal-text">
                        {{ $movementText }}
                        <strong>{{ $movementMonth }}</strong>
                    </p>
                </header>

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