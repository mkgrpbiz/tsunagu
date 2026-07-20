<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollaborationPartnerApplicationController extends Controller
{
    public function create(): View
    {
        return view('agency.collaboration_partner_applications.create', [
            'agency' => Auth::guard('agency')->user(),
        ]);
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'collaboration_content' => ['required', 'string'],
            'proposal_details' => ['required', 'string'],
            'expected_role' => ['required', 'string'],
            'reference_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $data['agency_id'] = Auth::guard('agency')->id();

        CollaborationPartnerApplication::create($data);

        return view('agency.collaboration_partner_applications.complete');
    }
}
