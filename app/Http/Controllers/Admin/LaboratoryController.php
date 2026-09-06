<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaboratoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $laboratories = Laboratory::visible()
            ->withCount('activeProducts')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.laboratories.index', compact('laboratories', 'search'));
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
            $file = $request->file('logo');

            $extension = strtolower($file->getClientOriginalExtension());

            $filename = Str::slug($request->name)
                . '-'
                . now()->format('YmdHis')
                . '-'
                . Str::random(8)
                . '.'
                . $extension;

            // En local:
            $destination = public_path('storage/laboratories');

            // En producción Hostinger, si usas public_html:
            $publicHtmlPath = base_path('../public_html/storage/laboratories');

            if (is_dir(base_path('../public_html'))) {
                $destination = $publicHtmlPath;
            }

            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $oldFilename = $laboratory->logo_path;
            $oldPath = $oldFilename ? $destination . '/' . $oldFilename : null;

            $file->move($destination, $filename);
            @chmod($destination . '/' . $filename, 0644);

            $data['logo_path'] = $filename;

            if (
                $oldPath &&
                $oldFilename !== $filename &&
                is_file($oldPath)
            ) {
                @unlink($oldPath);
            }
        }

        $laboratory->update($data);

        return redirect()
            ->route('admin.laboratories.index')
            ->with('success', 'Laboratorio actualizado correctamente.');
    }
}