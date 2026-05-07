<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-6">Registrar Nuevo Producto</h1>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block font-bold">Código (SKU/ID)</label>
                    <input type="text" name="id" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block font-bold">Nombre del Producto</label>
                    <input type="text" name="name" class="w-full border p-2 rounded" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block font-bold">Categoría</label>
                    <select name="category_id" class="w-full border p-2 rounded">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block font-bold">Laboratorio</label>
                    <select name="laboratory_id" class="w-full border p-2 rounded">
                        @foreach($laboratories as $lab)
                            <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="mb-4">
                    <label class="block font-bold">Precio</label>
                    <input type="number" step="0.01" name="price" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block font-bold">Cantidad/Stock</label>
                    <input type="number" name="cantidad" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block font-bold">Imagen</label>
                    <input type="file" name="image" class="w-full text-sm">
                </div>
            </div>

            <div class="flex gap-10 mt-4">
                <label><input type="checkbox" name="has_iva"> Tiene IVA</label>
                <label><input type="checkbox" name="is_controlled"> Controlado</label>
            </div>

            <button type="submit" class="mt-6 w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 font-bold">
                Guardar Producto
            </button>
        </form>
    </div>
</body>
</html>