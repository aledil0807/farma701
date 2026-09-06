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

            @if($report->report_month)
                <a href="{{ route('admin.controlled-products.month.show', ['month' => $report->report_month]) }}"
                    class="admin-btn admin-btn--secondary">
                    Volver al reporte
                </a>
            @else
                <a href="{{ route('admin.controlled-products.index') }}" class="admin-btn admin-btn--secondary">
                    Atrás
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="admin-alert admin-alert--error">
                <strong>Revisa la información ingresada.</strong>

                <ul class="admin-alert__list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-card controlled-products-form-card controlled-products-config-card" x-data="{
                                                selected: @js((array) old('selected_products', [])),
                                                opened: null,

                                                isSelected(code) {
                                                    return this.selected.includes(code);
                                                },

                                                selectProduct(code) {
                                                    if (!this.isSelected(code)) {
                                                        this.selected.push(code);
                                                    }

                                                    this.opened = code;
                                                },

                                                unselectProduct(code) {
                                                    this.selected = this.selected.filter(item => item !== code);

                                                    if (this.opened === code) {
                                                        this.opened = null;
                                                    }
                                                },

                                                toggleProduct(code) {
                                                    if (!this.isSelected(code)) {
                                                        this.selectProduct(code);
                                                        return;
                                                    }

                                                    this.opened = this.opened === code ? null : code;
                                                },

                                                handleCheckbox(code, checked) {
                                                    if (checked) {
                                                        this.selectProduct(code);
                                                        return;
                                                    }

                                                    this.unselectProduct(code);
                                                }
                                            }" @click.outside="opened = null">
            <div class="controlled-products-table-head">
                <div>
                    <h2 class="controlled-products-card-title">Agregar productos por factura</h2>
                    <p>
                        Selecciona los productos configurados para esta categoría y registra sus entradas o salidas.
                    </p>
                </div>
            </div>

            @if(count($configuredProducts ?? []) > 0)
                <form method="POST" action="{{ route('admin.controlled-products.configured-items.store', $report) }}"
                    class="controlled-products-form">
                    @csrf

                    <div class="controlled-products-form__grid controlled-products-form__grid--invoice">
                        <div class="admin-form__group">
                            <label class="admin-form__label">N° de factura</label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number') }}"
                                class="admin-form__input-text" placeholder="Ej: FAC-000123">
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label">Droguería</label>
                            <input type="text" name="drugstore" value="{{ old('drugstore') }}" class="admin-form__input-text"
                                placeholder="Ej: Droguería...">
                        </div>
                    </div>

                    <div class="controlled-products-config-list">
                        @foreach($configuredProducts as $configuredProduct)
                            @php
                                $code = $configuredProduct['code'];
                                $oldProduct = old('products.' . $code, []);
                                $defaultUnitsPerBox = $oldProduct['units_per_box'] ?? ($configuredProduct['units_per_box'] ?? 1);

                                $previousStock = (int) ($oldProduct['previous_stock'] ?? ($configuredProduct['previous_stock'] ?? 0));
                                $existingEntries = (int) ($configuredProduct['existing_entries'] ?? 0);
                                $existingExits = (int) ($configuredProduct['existing_exits'] ?? 0);
                                $existingCurrentStock = (int) ($configuredProduct['existing_current_stock'] ?? $previousStock);
                            @endphp

                            <article class="controlled-products-config-item" :class="{
                                                                                                                                'is-selected': isSelected(@js($code)),
                                                                                                                                'is-open': opened === @js($code)
                                                                                                                            }">
                                <div class="controlled-products-config-head">
                                    <label class="controlled-products-config-check" @click.stop>
                                        <input type="checkbox" name="selected_products[]" value="{{ $code }}"
                                            :checked="isSelected(@js($code))"
                                            @change="handleCheckbox(@js($code), $event.target.checked)">

                                        <span class="controlled-products-config-check__box"></span>
                                    </label>

                                    <button type="button" class="controlled-products-config-title-button"
                                        @click.stop="toggleProduct(@js($code))">
                                        <span class="controlled-products-config-check__content">
                                            <strong>{{ $configuredProduct['name'] }}</strong>

                                        </span>
                                    </button>

                                    <button type="button" class="controlled-products-config-toggle"
                                        :disabled="!isSelected(@js($code))" @click.stop="toggleProduct(@js($code))"
                                        aria-label="Abrir configuración del producto">
                                        <i class="fa-solid fa-chevron-down" :class="{ 'is-rotated': opened === @js($code) }"></i>
                                    </button>
                                </div>

                                <div x-cloak x-show="isSelected(@js($code)) && opened === @js($code)" x-transition
                                    class="controlled-products-config-details" data-controlled-config-row
                                    data-config-existing-entries="{{ $existingEntries }}"
                                    data-config-existing-exits="{{ $existingExits }}" @click.stop>
                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Unid. x caja</label>
                                        <input type="number" name="products[{{ $code }}][units_per_box]"
                                            value="{{ $defaultUnitsPerBox }}" min="1" class="admin-form__input-text"
                                            data-config-units>
                                    </div>

                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Existencia anterior</label>
                                        <input type="number" name="products[{{ $code }}][previous_stock]"
                                            value="{{ $previousStock }}" min="0" class="admin-form__input-text"
                                            data-config-previous>
                                    </div>

                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Cajas recibidas</label>
                                        <input type="number" name="products[{{ $code }}][boxes_received]"
                                            value="{{ $oldProduct['boxes_received'] ?? '' }}" min="0" class="admin-form__input-text"
                                            data-config-boxes>
                                    </div>

                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Entradas</label>
                                        <div class="controlled-products-inline-result">
                                            <strong data-config-entries>0</strong>
                                        </div>
                                    </div>

                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Cajas salidas</label>
                                        <input type="number" name="products[{{ $code }}][exits]"
                                            value="{{ $oldProduct['exits'] ?? '' }}" min="0" class="admin-form__input-text"
                                            data-config-exit-boxes>
                                    </div>

                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Salidas</label>
                                        <div class="controlled-products-inline-result">
                                            <strong data-config-exits>0</strong>
                                        </div>
                                    </div>

                                    <div class="admin-form__group">
                                        <label class="admin-form__label">Existencia actual</label>
                                        <div class="controlled-products-inline-result">
                                            <strong data-config-current>{{ $previousStock }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="controlled-products-form__actions">
                        <button type="submit" class="admin-btn admin-btn--primary">
                            <i class="fa-solid fa-plus"></i>
                            Agregar productos seleccionados
                        </button>
                    </div>
                </form>
            @else
                <div class="admin-alert admin-alert--warning">
                    No hay productos configurados para la categoría
                    <strong>{{ $report->category ?: 'Sin categoría' }}</strong>.
                    Debes agregarlos primero en el archivo de configuración.
                </div>
            @endif
        </div>

        <div class="admin-card controlled-products-table-card">
            <div class="controlled-products-table-head">
                <div>
                    <h2 class="controlled-products-card-title">Productos registrados</h2>
                    <p>
                        Estos productos pertenecen únicamente a este reporte. Las entradas y salidas se acumulan según cada
                        carga realizada.
                    </p>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table controlled-products-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Droguería</th>
                            <th>Facturas</th>
                            <th>Entradas</th>
                            <th>Exist. anterior</th>
                            <th>Salidas</th>
                            <th>Exist. actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($report->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->drugstore ?: '—' }}</td>
                                <td>{{ $item->invoice_number ?: '—' }}</td>

                                <td>
                                    <strong>{{ $item->entries }}</strong>
                                </td>

                                <td>
                                    {{ $item->previous_stock }}
                                </td>

                                <td>
                                    <strong>{{ $item->exits }}</strong>
                                </td>

                                <td>
                                    <strong>{{ $item->current_stock }}</strong>
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
                                <td colspan="8" class="admin-empty-text">
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
            document.querySelectorAll('[data-controlled-config-row]').forEach(function (row) {
                const unitsInput = row.querySelector('[data-config-units]');
                const previousInput = row.querySelector('[data-config-previous]');
                const boxesInput = row.querySelector('[data-config-boxes]');
                const exitBoxesInput = row.querySelector('[data-config-exit-boxes]');
                const entriesOutput = row.querySelector('[data-config-entries]');
                const exitsOutput = row.querySelector('[data-config-exits]');
                const currentOutput = row.querySelector('[data-config-current]');

                const existingEntries = Number(row.dataset.configExistingEntries || 0);
                const existingExits = Number(row.dataset.configExistingExits || 0);

                if (!unitsInput || !previousInput || !boxesInput || !exitBoxesInput || !entriesOutput || !exitsOutput) {
                    return;
                }

                function calculateConfiguredProduct() {
                    const unitsPerBox = Math.max(1, Number(unitsInput.value || 1));
                    const previousStock = Math.max(0, Number(previousInput.value || 0));
                    const boxesReceived = Math.max(0, Number(boxesInput.value || 0));
                    const exitBoxes = Math.max(0, Number(exitBoxesInput.value || 0));

                    const entries = unitsPerBox * boxesReceived;
                    const exits = unitsPerBox * exitBoxes;

                    const totalEntries = existingEntries + entries;
                    const totalExits = existingExits + exits;

                    const currentStock = previousStock + totalEntries - totalExits;

                    entriesOutput.textContent = entries;
                    exitsOutput.textContent = exits;

                    if (currentOutput) {
                        currentOutput.textContent = currentStock;
                    }
                }

                unitsInput.addEventListener('input', calculateConfiguredProduct);
                boxesInput.addEventListener('input', calculateConfiguredProduct);
                exitBoxesInput.addEventListener('input', calculateConfiguredProduct);
                previousInput.addEventListener('input', calculateConfiguredProduct);

                calculateConfiguredProduct();
            });
        });
    </script>
@endsection