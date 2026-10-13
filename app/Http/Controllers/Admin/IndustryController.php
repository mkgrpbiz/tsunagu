<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndustryController extends Controller
{
    public function index(): View
    {
        return view('admin.industries.index', [
            'industries' => Industry::withCount('projects')->orderBy('sort_order')->get(),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:industries,id'],
        ]);

        foreach (array_values($data['order']) as $index => $id) {
            Industry::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function create(): View
    {
        return view('admin.industries.create', ['industry' => new Industry]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = Industry::max('sort_order') + 1;

        Industry::create($data);

        return redirect()->route('admin.industries.index')->with('status', '業種カテゴリーを作成しました。');
    }

    public function edit(Industry $industry): View
    {
        return view('admin.industries.edit', ['industry' => $industry]);
    }

    public function update(Request $request, Industry $industry): RedirectResponse
    {
        $industry->update($this->validated($request));

        return redirect()->route('admin.industries.index')->with('status', '業種カテゴリーを更新しました。');
    }

    public function destroy(Industry $industry): RedirectResponse
    {
        if ($industry->projects()->exists()) {
            return back()->with('error', 'この業種カテゴリーには案件が紐づいているため削除できません。');
        }

        $industry->delete();

        return redirect()->route('admin.industries.index')->with('status', '業種カテゴリーを削除しました。');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
    }
}
