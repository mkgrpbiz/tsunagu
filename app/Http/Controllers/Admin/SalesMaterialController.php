<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SalesMaterialController extends Controller
{
    public function index(): View
    {
        return view('admin.sales_materials.index', [
            'materials' => SalesMaterial::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sales_materials.create', ['material' => new SalesMaterial]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        SalesMaterial::create([
            'title' => $data['title'],
            'file_path' => $request->file('file')->store('sales-materials', 'public'),
        ]);

        return redirect()->route('admin.sales-materials.index')->with('status', '営業素材を追加しました。');
    }

    public function edit(SalesMaterial $salesMaterial): View
    {
        return view('admin.sales_materials.edit', ['material' => $salesMaterial]);
    }

    public function update(Request $request, SalesMaterial $salesMaterial): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($salesMaterial->file_path);
            $data['file_path'] = $request->file('file')->store('sales-materials', 'public');
        }

        $salesMaterial->update($data);

        return redirect()->route('admin.sales-materials.index')->with('status', '営業素材を更新しました。');
    }

    public function destroy(SalesMaterial $salesMaterial): RedirectResponse
    {
        Storage::disk('public')->delete($salesMaterial->file_path);
        $salesMaterial->delete();

        return redirect()->route('admin.sales-materials.index')->with('status', '営業素材を削除しました。');
    }
}
