<?php

namespace App\Http\Controllers\Agency;

use App\Enums\AgencyStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\HomeBlock;
use App\Models\HomePageContent;
use App\Models\SalesMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $agency = Auth::guard('agency')->user();

        $restrictedReason = match (true) {
            $agency->status !== AgencyStatus::Approved => 'pending_review',
            ! $agency->hasSubmittedAllConsents() => 'consent_required',
            default => null,
        };

        $bannerReason = $restrictedReason ?? (! $agency->line_uid ? 'line_required' : null);

        return view('agency.home.index', [
            'agency' => $agency,
            'content' => HomePageContent::current(),
            'blocks' => HomeBlock::orderBy('sort_order')->get(),
            'announcements' => Announcement::latest()->take(10)->get(),
            'salesMaterials' => SalesMaterial::latest()->get(),
            'referralUrl' => url('/agency/register?ref='.$agency->referral_code),
            'restrictedReason' => $restrictedReason,
            'bannerReason' => $bannerReason,
            'liffId' => config('services.line.liff_id'),
        ]);
    }
}
