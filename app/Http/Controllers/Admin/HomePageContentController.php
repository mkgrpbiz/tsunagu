<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomePageContentController extends Controller
{
    public function edit(): View
    {
        return view('admin.home_content.edit', [
            'content' => HomePageContent::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_tagline' => ['required', 'string', 'max:255'],
            'closing_message' => ['required', 'string'],
            'brand_logo' => ['nullable', 'image', 'max:4096'],
            'remove_brand_logo' => ['nullable', 'boolean'],
        ]);

        $content = HomePageContent::current();

        if ($request->hasFile('brand_logo')) {
            if ($content->brand_logo_path) {
                Storage::disk('public')->delete($content->brand_logo_path);
            }
            $data['brand_logo_path'] = $request->file('brand_logo')->store('brand', 'public');
        } elseif ($request->boolean('remove_brand_logo')) {
            if ($content->brand_logo_path) {
                Storage::disk('public')->delete($content->brand_logo_path);
            }
            $data['brand_logo_path'] = null;
        }

        unset($data['brand_logo'], $data['remove_brand_logo']);

        $content->update($data);

        return redirect()->route('admin.home-content.edit')->with('status', 'ホーム画面のテキストを更新しました。');
    }
}
