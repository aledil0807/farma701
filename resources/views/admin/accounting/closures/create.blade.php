@extends('layouts.admin')

@section('content')
    <div class="admin-page accounting-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Nuevo cierre de caja</h1>

            </div>

            <a href="{{ route('admin.accounting.closures.index') }}" class="admin-btn admin-btn--secondary">
                Volver
            </a>
        </div>

        @if($errors->any())
            <div class="admin-alert admin-alert--error">
                Revisa los campos del formulario.
            </div>
        @endif

        @if($cashiers->isEmpty())
            <div class="admin-alert admin-alert--error">
                Primero debes crear al menos un cajero para poder registrar un cierre.
                <a href="{{ route('admin.accounting.cashiers.index') }}">
                    Crear cajero
                </a>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.accounting.closures.store') }}" class="accounting-form">
            @csrf

            <div class="admin-card accounting-card">
                <h2 class="accounting-card-title">Datos del cierre</h2>

                <div class="accounting-form-grid">
                    <div class="admin-form__group">
                        <label class="admin-form__label">Fecha del cierre</label>
                        <input type="date" name="closure_date" value="{{ old('closure_date', now()->format('Y-m-d')) }}"
                            class="admin-form__input-text" required>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Cajero</label>
                        <select name="cashier_id" class="admin-form__input-text" required>
                            <option value="">Seleccionar cajero</option>

                            @foreach($cashiers as $cashier)
                                <option value="{{ $cashier->id }}" @selected(old('cashier_id') == $cashier->id)>
                                    {{ $cashier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Turno</label>
                        <select name="shift" class="admin-form__input-text">
                            <option value="">Sin turno</option>
                            <option value="mañana" @selected(old('shift') === 'mañana')>Mañana</option>
                            <option value="tarde" @selected(old('shift') === 'tarde')>Tarde</option>
                            <option value="noche" @selected(old('shift') === 'noche')>Noche</option>
                            <option value="completo" @selected(old('shift') === 'completo')>Día completo</option>
                        </select>
                    </div>

                    <div class="admin-form__group">
                        <label class="admin-form__label">Estado</label>
                        <select name="status" class="admin-form__input-text">
                            <option value="open" @selected(old('status', 'open') === 'open')>Abierto</option>
                            <option value="closed" @selected(old('status') === 'closed')>Cerrado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="admin-card accounting-card">
                <h2 class="accounting-card-title">Métodos de pago</h2>

                <div class="admin-table-wrapper">
                    <table class="admin-table accounting-table accounting-closure-table" data-accounting-table>
                        <thead>
                            <tr>
                                <th>Método</th>
                                <th>Facturado Bs</th>
                                <th>Entregado Bs</th>
                                <th>Diferencia Bs</th>
                                <th>Total transacciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($paymentMethods as $paymentMethod)
                                <tr data-accounting-row>
                                    <td>
                                        <strong>{{ $paymentMethod->name }}</strong>
                                    </td>

                                    <td>
                                        <input type="text" name="payment_totals[{{ $paymentMethod->id }}][facturado_bs]"
                                            value="{{ old('payment_totals.' . $paymentMethod->id . '.facturado_bs', '0,00') }}"
                                            class="admin-form__input-text accounting-money-input" data-facturado>
                                    </td>

                                    <td>
                                        <input type="text" name="payment_totals[{{ $paymentMethod->id }}][entregado_bs]"
                                            value="{{ old('payment_totals.' . $paymentMethod->id . '.entregado_bs', '0,00') }}"
                                            class="admin-form__input-text accounting-money-input" data-entregado>
                                    </td>

                                    <td>
                                        <span class="accounting-difference" data-difference>
                                            Bs. 0,00
                                        </span>
                                    </td>

                                    <td>
                                        <input type="number" name="payment_totals[{{ $paymentMethod->id }}][transactions_count]"
                                            value="{{ old('payment_totals.' . $paymentMethod->id . '.transactions_count', 0) }}"
                                            min="0" class="admin-form__input-text accounting-transactions-input"
                                            data-transactions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th data-total-facturado>Bs. 0,00</th>
                                <th data-total-entregado>Bs. 0,00</th>
                                <th data-total-difference>Bs. 0,00</th>
                                <th data-total-transactions>0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="admin-card accounting-card">
                <div class="admin-form__group">
                    <label class="admin-form__label">Notas</label>
                    <textarea name="notes" class="admin-form__input-text accounting-notes"
                        placeholder="Observaciones del cierre">{{ old('notes') }}</textarea>
                </div>

                <div class="accounting-actions">
                    <button type="submit" class="admin-btn admin-btn--primary" @disabled($cashiers->isEmpty())>
                        Guardar cierre
                    </button>
                </div>
            </div>
        </form>
    </div>

    @include('admin.accounting.closures.partials.calculator-script')
@endsection