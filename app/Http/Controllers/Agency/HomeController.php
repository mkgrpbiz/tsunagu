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

        if ($agency->status !== AgencyStatus::Approved) {
            return view('agency.restricted', [
                'agency' => $agency,
                'liffId' => config('services.line_partner.liff_id'),
            ]);
        }

        $restrictedReason = ! $agency->hasSubmittedAllConsents() ? 'consent_required' : null;
        $bannerReason = $restrictedReason ?? (! $agency->line_uid ? 'line_required' : null);

        return view('agency.home.index', [
            'agency' => $agency,
            'content' => HomePageContent::current(),
            'blocks' => HomeBlock::orderBy('sort_order')->get(),
            'announcements' => Announcement::where('is_draft', false)->latest()->take(10)->get(),
            'salesMaterials' => SalesMaterial::where('is_draft', false)->latest()->get(),
            'referralUrl' => url('/agency/register?ref='.$agency->referral_code),
            'referredPartnerCount' => $agency->referrals()->whereNotNull('line_uid')->where('is_collaboration_partner', false)->count(),
            'referredCollaborationPartnerCount' => $agency->referrals()->whereNotNull('line_uid')->where('is_collaboration_partner', true)->count(),
            'restrictedReason' => $restrictedReason,
            'bannerReason' => $bannerReason,
            'liffId' => config('services.line_partner.liff_id'),
        ]);
    }
}
