<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageContent;
use App\Services\AppIconGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppIconController extends Controller
{
    public function edit(): View
    {
        return view('admin.app_icon.edit', [
            'content' => HomePageContent::current(),
        ]);
    }

    public function update(Request $request, AppIconGenerator $generator): RedirectResponse
    {
        $data = $request->validate([
            'app_icon' => ['required', 'image', 'max:4096'],
        ]);

        $content = HomePageContent::current();

        if ($content->app_icon_source_path) {
            Storage::disk('public')->delete($content->app_icon_source_path);
        }

        $sourcePath = $request->file('app_icon')->store('branding', 'public');
        $content->update(['app_icon_source_path' => $sourcePath]);

        $generator->generate(Storage::disk('public')->path($sourcePath));

        return redirect()->route('admin.app-icon.edit')->with('status', 'アイコンを更新しました。');
    }
}
