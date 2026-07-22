<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function show(): View
    {
        return view('public.company_profile.show', [
            'profile' => CompanyProfile::current(),
        ]);
    }
}
