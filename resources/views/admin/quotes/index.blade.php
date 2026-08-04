@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page__header">
            <div>
                <h1 class="admin-page__title">Presupuestos</h1>
                
            </div>

            <form method="POST" action="{{ route('admin.quotes.store') }}" class="quote-create-form">
                @csrf

                <input type="text" name="employee_name" class="admin-form__input-text" placeholder="Creado por..."
                    required>

                <button type="submit" class="admin-btn admin-btn--primary">
                    Nuevo presupuesto
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="admin-alert admin-alert--success">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-card">
            @if($quotes->isEmpty())
                <p>No hay presupuestos creados todavía.</p>
            @else
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($quotes as $quote)
                            <tr>
                                <td>
                                    {{ $quote->employee_name ?? 'Usuario no indicado' }}
                                </td>

                                <td>
                                    {{ $quote->created_at->format('d/m/Y H:i') }}
                                </td>

                                <td>
                                    <a href="{{ route('admin.quotes.edit', $quote) }}" class="admin-btn admin-btn--secondary">
                                        Abrir
                                    </a>

                                    <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" style="display:inline;"
                                        onsubmit="return confirm('¿Eliminar este presupuesto?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="admin-btn admin-btn--danger">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $quotes->links() }}
            @endif
        </div>
    </div>
@endsection