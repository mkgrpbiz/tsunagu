<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\HomeBlock;
use App\Models\SalesMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeBlockController extends Controller
{
    private const CREATABLE_TYPES = ['text', 'image', 'benefits', 'cta'];

    public function index(): View
    {
        return view('admin.home_blocks.index', [
            'blocks' => HomeBlock::orderBy('sort_order')->get(),
            'salesMaterials' => SalesMaterial::latest()->get(),
            'announcements' => Announcement::latest()->take(10)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.home_blocks.create', [
            'block' => new HomeBlock,
            'types' => self::CREATABLE_TYPES,
            'colors' => HomeBlock::COLORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(self::CREATABLE_TYPES)],
        ]);

        $data = array_merge($data, $this->validatedContent($request, $data['type']));
        $data['sort_order'] = (int) HomeBlock::max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('home-blocks', 'public');
        }

        HomeBlock::create($data);

        return redirect()->route('admin.home-blocks.index')->with('status', 'ブロックを作成しました。');
    }

    public function edit(HomeBlock $homeBlock): View
    {
        return view('admin.home_blocks.edit', [
            'block' => $homeBlock,
            'colors' => HomeBlock::COLORS,
        ]);
    }

    public function update(Request $request, HomeBlock $homeBlock): RedirectResponse
    {
        $data = $this->validatedContent($request, $homeBlock->type);

        if ($request->hasFile('image')) {
            if ($homeBlock->image_path) {
                Storage::disk('public')->delete($homeBlock->image_path);
            }
            $data['image_path'] = $request->file('image')->store('home-blocks', 'public');
        }

        $homeBlock->update($data);

        return redirect()->route('admin.home-blocks.index')->with('status', 'ブロックを更新しました。');
    }

    public function destroy(HomeBlock $homeBlock): RedirectResponse
    {
        if ($homeBlock->image_path) {
            Storage::disk('public')->delete($homeBlock->image_path);
        }

        $homeBlock->delete();

        return redirect()->route('admin.home-blocks.index')->with('status', 'ブロックを削除しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:home_blocks,id'],
        ]);

        foreach (array_values($data['order']) as $index => $id) {
            HomeBlock::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function validatedContent(Request $request, string $type): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'color' => ['nullable', Rule::in(HomeBlock::COLORS)],
            'image' => ['nullable', 'image', 'max:4096'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'button_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if ($type === 'image' && ! $request->hasFile('image') && blank($request->route('home_block')?->image_path)) {
            $request->validate(['image' => ['required']]);
        }

        if ($type === 'cta') {
            $request->validate([
                'button_text' => ['required', 'string', 'max:50'],
                'button_url' => ['required', 'url', 'max:2048'],
            ]);
        } else {
            $data['button_text'] = null;
            $data['button_url'] = null;
        }

        $data['color'] = $type === 'text' ? ($data['color'] ?: 'gray') : null;

        unset($data['image']);

        return $data;
    }
}
