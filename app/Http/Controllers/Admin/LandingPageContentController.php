<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageContent;
use App\Models\LandingPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageContentController extends Controller
{
    public function edit(): View
    {
        return view('admin.landing_page_content.edit', [
            'content' => LandingPageContent::current(),
            'homeContent' => HomePageContent::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tagline' => ['required', 'string', 'max:255'],
            'hero_line1' => ['required', 'string', 'max:255'],
            'hero_highlight' => ['required', 'string', 'max:255'],
            'hero_suffix' => ['required', 'string', 'max:255'],
            'steps_title' => ['required', 'string', 'max:255'],
            'step1' => ['required', 'string', 'max:255'],
            'step2' => ['required', 'string', 'max:255'],
            'step3' => ['required', 'string', 'max:255'],
            'benefits_title' => ['required', 'string', 'max:255'],
            'benefits_body' => ['nullable', 'string'],
            'cta_text' => ['required', 'string', 'max:255'],
            'brand_badge_text' => ['nullable', 'string', 'max:255'],
            'brand_logo' => ['nullable', 'image', 'max:4096'],
            'remove_brand_logo' => ['nullable', 'boolean'],
        ]);

        $homeContent = HomePageContent::current();

        if ($request->hasFile('brand_logo')) {
            if ($homeContent->brand_logo_path) {
                Storage::disk('public')->delete($homeContent->brand_logo_path);
            }
            $homeContent->update(['brand_logo_path' => $request->file('brand_logo')->store('brand', 'public')]);
        } elseif ($request->boolean('remove_brand_logo')) {
            if ($homeContent->brand_logo_path) {
                Storage::disk('public')->delete($homeContent->brand_logo_path);
            }
            $homeContent->update(['brand_logo_path' => null]);
        }

        unset($data['brand_logo'], $data['remove_brand_logo']);

        LandingPageContent::current()->update($data);

        return redirect()->route('admin.landing-page-content.edit')->with('status', 'LPのテキストを更新しました。');
    }
}
