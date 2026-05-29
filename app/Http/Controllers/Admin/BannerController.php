<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->orderByDesc('id')->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $image = $request->file('image');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());

        $destination = public_path('storage/banners');

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $image->move($destination, $filename);

        @chmod($destination . '/' . $filename, 0644);

        Banner::create([
            'title' => $request->title,
            'link' => $request->link,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'is_active' => $request->boolean('is_active'),
            'image_path' => $filename,
        ]);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner creado correctamente.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $data = [
            'title' => $request->title,
            'link' => $request->link,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            $oldPath = public_path('storage/banners/' . $banner->image_path);

            $image = $request->file('image');
            $filename = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());

            $destination = public_path('storage/banners');

            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $image->move($destination, $filename);
            @chmod($destination . '/' . $filename, 0644);

            $data['image_path'] = $filename;

            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $banner->update($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner actualizado correctamente.');
    }

    public function destroy(Banner $banner)
    {
        $path = public_path('storage/banners/' . $banner->image_path);

        if (is_file($path)) {
            @unlink($path);
        }

        $banner->delete();

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner eliminado correctamente.');
    }
}