<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProductRequest;
use Illuminate\Http\Request;

class CustomerProductRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'searched_product' => ['required', 'string', 'max:255'],
        ]);

        CustomerProductRequest::create($data);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Recordatorio agregado correctamente.');
    }
    public function markAsSent(CustomerProductRequest $productRequest)
    {
        $productRequest->update([
            'sent_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Recordatorio marcado como enviado.');
    }
}