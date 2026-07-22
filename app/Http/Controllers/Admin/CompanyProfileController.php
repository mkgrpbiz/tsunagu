<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.company_profile.edit', [
            'profile' => CompanyProfile::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'representative_title' => ['nullable', 'string', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'business_description' => ['nullable', 'string'],
            'services' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'business_hours' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $profile = CompanyProfile::current();

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = null;
        }

        unset($data['logo'], $data['remove_logo']);

        $profile->update($data);

        return redirect()->route('admin.company-profile.edit')->with('status', '会社概要を更新しました。');
    }
}
