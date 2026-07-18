<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\View\View;

class CollaborationPartnerController extends Controller
{
    public function index(): View
    {
        return view('admin.collaboration_partners.index', [
            'agencies' => Agency::withCount(['projects' => fn ($query) => $query->where('status', ProjectStatus::Published)])
                ->with('referredBy')
                ->where('is_collaboration_partner', true)
                ->orderByDesc('collaboration_partner_at')
                ->get(),
        ]);
    }
}
