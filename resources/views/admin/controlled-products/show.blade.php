@extends('layouts.admin')

@section('content')
    <div class="admin-page controlled-products-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">
                    {{ $report->title ?: 'Reporte de productos controlados' }}
                </h1>
                <p class="admin-page__subtitle">
                    {{ $report->report_number }}
                    |
                    Mes: {{ $report->report_month }}
                    |
                    Categoría: {{ $report->category ?: 'Sin categoría' }}
                </p>
            </div>

            <a href="{{ route('admin.controlled-products.index') }}" class="admin-btn admin-btn--secondary">
                Volver a reportes
            </a>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-card controlled-products-form-card">
            <h2 class="controlled-products-card-title">Agregar producto al reporte</h2>

            <form method="POST" action="{{ route('admin.controlled-products.items.store', $report) }}"
                class="controlled-products-form" data-controlled-calculator>
                @csrf

                <div class="controlled-products-form__grid">
                    <div class="admin-form__group">
                        <label class="admin-form__label">Nombre del producto</label>
                        <input type="text" name="product_name" value="{{ old('product_name') }}"
                            class="admin-form__input-text" required>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Droguería</label>
                        <input type="text" name="drugstore" value="{{ old('drugstore') }}" class="admin-form__input-text">
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">N° de factura</label>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number') }}"
                            class="admin-form__input-text">
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Unidades por caja</label>
                        <input type="number" name="units_per_box" value="{{ old('units_per_box', 1) }}" min="1"
                            class="admin-form__input-text" data-controlled-units-per-box required>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Cajas recibidas</label>
                        <input type="number" name="boxes_received" value="{{ old('boxes_received', 0) }}" min="0"
                            class="admin-form__input-text" data-controlled-boxes>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Existencia anterior</label>
                        <input type="number" name="previous_stock" value="{{ old('previous_stock', 0) }}" min="0"
                            class="admin-form__input-text" data-controlled-previous required>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Entradas</label>
                        <div class="controlled-products-inline-result">
                            <strong data-controlled-entries-output>0</strong>
                        </div>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Salidas</label>
                        <input type="number" name="exits" value="{{ old('exits', 0) }}" min="0"
                            class="admin-form__input-text" data-controlled-exits required>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Existencia actual</label>
                        <div class="controlled-products-inline-result">
                            <strong data-controlled-current>0</strong>
                        </div>
                    </div>
                </div>

                <div class="controlled-products-form__actions">
                    <button type="submit" class="admin-btn admin-btn--primary">
                        Agregar producto
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-card controlled-products-table-card">
            <div class="controlled-products-table-head">
                <div>
                    <h2 class="controlled-products-card-title">Productos registrados</h2>
                    <p>Estos productos pertenecen únicamente a este reporte.</p>
                </div>

                <button type="submit" form="controlledProductsBulkForm" class="admin-btn admin-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                </button>

                <a href="{{ route('admin.controlled-products.pdf', $report) }}" class="admin-btn admin-btn--secondary"
                    target="_blank">
                    <i class="fa-solid fa-file-pdf"></i>
                    Exportar PDF
                </a>
            </div>

            <form id="controlledProductsBulkForm" method="POST"
                action="{{ route('admin.controlled-products.items.update-bulk', $report) }}">
                @csrf
                @method('PUT')
            </form>

            <div class="admin-table-wrapper">
                <table class="admin-table controlled-products-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Droguería</th>
                            <th>Factura</th>
                            <th>Unid. x caja</th>
                            <th>Cajas</th>
                            <th>Entradas</th>
                            <th>Exist. anterior</th>
                            <th>Salidas</th>
                            <th>Exist. actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($report->items as $item)
                            @php
                                $updateFormId = 'controlled-product-item-update-' . $item->id;
                            @endphp

                            <tr data-controlled-calculator>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->drugstore ?: '—' }}</td>

                                <td>
                                    <input type="text" name="items[{{ $item->id }}][invoice_number]"
                                        value="{{ $item->invoice_number }}"
                                        class="admin-form__input-text controlled-products-table-input"
                                        form="controlledProductsBulkForm" placeholder="Factura">
                                </td>

                                <td>
                                    <input type="number" name="items[{{ $item->id }}][units_per_box]"
                                        value="{{ $item->units_per_box ?: 1 }}" min="1"
                                        class="admin-form__input-text controlled-products-table-input"
                                        form="controlledProductsBulkForm" data-controlled-units-per-box>
                                </td>

                                <td>
                                    <input type="number" name="items[{{ $item->id }}][boxes_received]"
                                        value="{{ $item->boxes_received ?? 0 }}" min="0"
                                        class="admin-form__input-text controlled-products-table-input"
                                        form="controlledProductsBulkForm" data-controlled-boxes>
                                </td>

                                <td>
                                    <strong data-controlled-entries-output>
                                        {{ $item->entries }}
                                    </strong>
                                </td>

                                <td>
                                    <span data-controlled-previous-value="{{ $item->previous_stock }}">
                                        {{ $item->previous_stock }}
                                    </span>
                                </td>

                                <td>
                                    <input type="number" name="items[{{ $item->id }}][exits]"
                                        value="{{ ($item->units_per_box ?? 1) > 0 ? (int) floor($item->exits / ($item->units_per_box ?: 1)) : $item->exits }}"
                                        min="0" class="admin-form__input-text controlled-products-table-input"
                                        form="controlledProductsBulkForm" data-controlled-exits>
                                </td>

                                <td>
                                    <strong data-controlled-current>
                                        {{ $item->current_stock }}
                                    </strong>
                                </td>

                                <td>
                                    <form method="POST" action="{{ route('admin.controlled-products.items.destroy', $item) }}"
                                        onsubmit="return confirm('¿Eliminar este producto del reporte?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="admin-btn admin-btn--danger">
                                            Eliminar
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="admin-empty-text">
                                    Este reporte todavía no tiene productos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-controlled-calculator]').forEach(function (calculator) {
                const unitsInput = calculator.querySelector('[data-controlled-units-per-box]');
                const boxesInput = calculator.querySelector('[data-controlled-boxes]');
                const previousInput = calculator.querySelector('[data-controlled-previous]');
                const previousValueElement = calculator.querySelector('[data-controlled-previous-value]');
                const exitsInput = calculator.querySelector('[data-controlled-exits]');
                const entriesOutput = calculator.querySelector('[data-controlled-entries-output]');
                const currentOutput = calculator.querySelector('[data-controlled-current]');
                const productNameInput = calculator.querySelector('input[name="product_name"]');

                if (!unitsInput || !boxesInput || !exitsInput || !entriesOutput || !currentOutput) return;

                function getPreviousStock() {
                    if (previousInput) {
                        return Number(previousInput.value || 0);
                    }

                    if (previousValueElement) {
                        return Number(previousValueElement.dataset.controlledPreviousValue || 0);
                    }

                    return 0;
                }

                function calculate() {
                    const unitsPerBox = Math.max(1, Number(unitsInput.value || 1));
                    const boxes = Math.max(0, Number(boxesInput.value || 0));
                    const previous = getPreviousStock();
                    const exitBoxes = Math.max(0, Number(exitsInput.value || 0));

                    const entries = unitsPerBox * boxes;
                    const exits = unitsPerBox * exitBoxes;
                    const current = previous + entries - exits;

                    entriesOutput.textContent = entries;
                    currentOutput.textContent = current;
                }

                function detectUnitsFromProductName() {
                    if (!productNameInput) return;

                    const currentUnits = Number(unitsInput.value || 1);

                    if (currentUnits > 1) return;

                    const match = productNameInput.value.match(/(?:x|\bpor\b)\s*(\d+)/i);

                    if (!match) return;

                    unitsInput.value = match[1];
                    calculate();
                }

                unitsInput.addEventListener('input', calculate);
                boxesInput.addEventListener('input', calculate);
                exitsInput.addEventListener('input', calculate);

                if (previousInput) {
                    previousInput.addEventListener('input', calculate);
                }

                if (productNameInput) {
                    productNameInput.addEventListener('input', detectUnitsFromProductName);
                    productNameInput.addEventListener('change', detectUnitsFromProductName);
                }

                calculate();
            });
        });
    </script>
@endsection