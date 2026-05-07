<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="p-6">
    <div class="mb-8 max-w-xl mx-auto">
        <input wire:model.live="search" type="text" placeholder="Buscar por nombre o laboratorio..."
            class="w-full px-4 py-3 rounded-full border-2 border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300 shadow-lg">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($products as $product)
            <div
                class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition duration-300 border border-gray-100">
                <div class="h-48 bg-gray-200 flex items-center justify-center">
                    @if($product->image_path)
                        <img src="{{$product->image_path}}" alt="{{ $product->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="text-gray-400">Sin imagen</span>
                    @endif
                </div>
                <div class="p-4">
                    <span class="text-xs font-semibold text-blue-600 uppercase">{{ $product->laboratory->name }}</span>
                    <h3 class="text-lg font-bold text-gray-800 truncate">{{ $product->name }}</h3>
                    <p class="text-gray-500 text-sm mb-2">Ref: {{ $product->id }}</p>

                    <div class="flex justify-between items-center mt-4">
                        <span class="text-2xl font-bold text-green-600">${{ number_format($product->price, 2) }}</span>
                        @if($product->has_iva)
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">IVA</span>
                        @endif
                    </div>

                    @if($product->is_controlled)
                        <p class="mt-2 text-red-500 text-xs font-bold">⚠️ PRODUCTO CONTROLADO</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>