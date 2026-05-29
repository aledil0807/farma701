<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laboratory;
use Illuminate\Http\Request;

class LaboratoryController extends Controller
{
    public function index()
    {
        $laboratories = Laboratory::orderBy('name')->get();

        return view('admin.laboratories.index', compact('laboratories'));
    }

    public function edit(Laboratory $laboratory)
    {
        return view('admin.laboratories.edit', compact('laboratory'));
    }

    public function update(Request $request, Laboratory $laboratory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $data = [
            'name' => $request->name,
        ];

        if ($request->hasFile('logo')) {
            $oldPath = public_path('storage/laboratories/' . $laboratory->logo_path);

            $file = $request->file('logo');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

            $destination = public_path('storage/laboratories');

            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);
            @chmod($destination . '/' . $filename, 0644);

            $data['logo_path'] = $filename;

            if ($laboratory->logo_path && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $laboratory->update($data);

        return redirect()
            ->route('admin.laboratories.index')
            ->with('success', 'Laboratorio actualizado correctamente.');
    }
}