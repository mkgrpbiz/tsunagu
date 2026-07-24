<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\ReferralCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternalAgencyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $searchResults = collect();

        if ($search !== '') {
            $searchResults = Agency::where('is_system', false)
                ->where('is_internal_use', false)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kana', 'like', "%{$search}%")
                        ->orWhere('legacy_code', 'like', "%{$search}%");

                    if (preg_match('/^b0*(\d+)$/i', $search, $matches)) {
                        $q->orWhere('id', (int) $matches[1]);
                    } elseif (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        }

        $internalAgencies = Agency::withCount('referrals')
            ->where('is_internal_use', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Agency $agency) {
                $agency->referral_commission_total = ReferralCommission::where('referrer_agency_id', $agency->id)->sum('amount');

                return $agency;
            });

        return view('admin.internal_agencies.index', [
            'internalAgencies' => $internalAgencies,
            'search' => $search,
            'searchResults' => $searchResults,
        ]);
    }

    public function create(): View
    {
        return view('admin.internal_agencies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'legacy_code' => ['required', 'string', 'max:255', Rule::unique('agencies', 'legacy_code')],
        ]);

        Agency::create([
            'name' => $data['name'],
            'name_kana' => $data['name_kana'],
            'legacy_code' => $data['legacy_code'],
            'gender' => Gender::Other,
            'prefecture' => '社内',
            'phone' => '00000000000',
            'email' => 'internal-'.Str::slug($data['legacy_code']).'@internal.tsunagu.local',
            'password' => 'pass1234',
            'must_change_password' => true,
            'status' => AgencyStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => Auth::id(),
            'is_internal_use' => true,
        ]);

        return redirect()->route('admin.internal-agencies.index')->with('status', '社内運用アカウントを作成しました。');
    }

    public function toggle(Agency $agency): RedirectResponse
    {
        $agency->update(['is_internal_use' => ! $agency->is_internal_use]);

        return redirect()->route('admin.internal-agencies.index')->with(
            'status',
            $agency->is_internal_use ? '社内運用アカウントに設定しました。' : '社内運用アカウントの指定を解除しました。'
        );
    }
}
