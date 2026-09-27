@extends('layouts.admin')

@section('content')
    <div class="admin-page accounting-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Cajeros</h1>
                
            </div>

            <div class="controlled-products-actions">
                <a href="{{ route('admin.accounting.closures.index') }}" class="admin-btn admin-btn--secondary">
                    Ver cierres
                </a>

                <a href="{{ route('admin.accounting.summary') }}" class="admin-btn admin-btn--secondary">
                    Ver resumen diario
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="admin-alert admin-alert--error">
                Revisa los campos del formulario.
            </div>
        @endif

        <div class="admin-card accounting-card">
            <h2 class="accounting-card-title">Crear cajero</h2>

            <form method="POST" action="{{ route('admin.accounting.cashiers.store') }}" class="cashier-create-form">
                @csrf

                <div class="cashier-form-grid">
                    <div class="admin-form__group">
                        <label class="admin-form__label">Nombre del cajero</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="admin-form__input-text"
                            placeholder="Ej: María Pérez" required>
                    </div>


                </div>



                <div class="accounting-actions">
                    <button type="submit" class="admin-btn admin-btn--primary">
                        Crear cajero
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-card accounting-card">
            <h2 class="accounting-card-title">Cajeros registrados</h2>

            <div class="admin-table-wrapper">
                <table class="admin-table accounting-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>

                            <th>Cierres registrados</th>
                            <th>Estado</th>

                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($cashiers as $cashier)
                            <tr>
                                <td>
                                    <strong>{{ $cashier->name }}</strong>
                                </td>



                                <td>
                                    {{ $cashier->closures_count }}
                                </td>

                                <td>
                                    <span
                                        class="cashier-status {{ $cashier->is_active ? 'cashier-status--active' : 'cashier-status--inactive' }}">
                                        {{ $cashier->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>



                                <td>
                                    <div class="controlled-products-actions">
                                        @if((int) $cashier->closures_count > 0)
                                            <form method="POST"
                                                action="{{ route('admin.accounting.cashiers.toggle-status', $cashier) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                    class="admin-btn {{ $cashier->is_active ? 'admin-btn--secondary' : 'admin-btn--primary' }}">
                                                    {{ $cashier->is_active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.accounting.cashiers.destroy', $cashier) }}"
                                                onsubmit="return confirm('¿Eliminar este cajero? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="admin-btn admin-btn--danger">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-empty-text">
                                    Todavía no hay cajeros registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection