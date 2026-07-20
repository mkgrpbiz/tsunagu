<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\CollaborationReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollaborationReferralController extends Controller
{
    public function create(): View
    {
        return view('agency.collaboration_referrals.create', [
            'agency' => Auth::guard('agency')->user(),
        ]);
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'referred_name' => ['required', 'string', 'max:255'],
            'referred_company' => ['nullable', 'string', 'max:255'],
            'referred_business' => ['required', 'string'],
            'referred_track_record' => ['required', 'string'],
            'reason' => ['required', 'string'],
            'consent_obtained' => ['required', 'in:1,0'],
        ]);

        $data['agency_id'] = Auth::guard('agency')->id();

        CollaborationReferral::create($data);

        return view('agency.collaboration_referrals.complete');
    }
}
