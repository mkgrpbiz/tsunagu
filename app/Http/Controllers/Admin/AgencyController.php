<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\BankAccountType;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyStatusHistory;
use App\Models\LegalDocumentConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $statusCounts = Agency::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        $agencies = Agency::withCount(['inquiries', 'referrals'])
            ->with('referredBy')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.agencies.index', [
            'agencies' => $agencies,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalCount' => Agency::count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.agencies.create', [
            'agency' => new Agency,
            'genders' => Gender::cases(),
            'bankAccountTypes' => BankAccountType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = 'pass1234';
        $data['must_change_password'] = true;
        $data['status'] = AgencyStatus::Approved;
        $data['approved_at'] = now();
        $data['approved_by_user_id'] = Auth::id();

        $agency = Agency::create($data);

        AgencyStatusHistory::create([
            'agency_id' => $agency->id,
            'from_status' => null,
            'to_status' => AgencyStatus::Approved,
            'changed_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('admin.agencies.index')->with('status', 'パートナーを作成しました。初期パスワードは pass1234 です。');
    }

    public function show(Agency $agency): View
    {
        return view('admin.agencies.show', [
            'agency' => $agency,
            'consents' => LegalDocumentConsent::where('agency_id', $agency->id)
                ->with('legalDocument')
                ->get()
                ->keyBy(fn (LegalDocumentConsent $consent) => $consent->legalDocument->type->value),
            'statusHistories' => $agency->statusHistories()->with('changedByUser')->latest('created_at')->get(),
        ]);
    }

    public function edit(Agency $agency): View
    {
        return view('admin.agencies.edit', [
            'agency' => $agency,
            'genders' => Gender::cases(),
            'bankAccountTypes' => BankAccountType::cases(),
        ]);
    }

    public function update(Request $request, Agency $agency): RedirectResponse
    {
        $agency->update($this->validated($request, $agency));

        return redirect()->route('admin.agencies.index')->with('status', 'パートナー情報を更新しました。');
    }

    public function updateStatus(Request $request, Agency $agency): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AgencyStatus::class)],
            'review_note' => ['nullable', 'string'],
        ]);

        $fromStatus = $agency->status;
        $toStatus = AgencyStatus::from($data['status']);

        $updates = [
            'status' => $toStatus,
            'review_note' => $data['review_note'] ?? $agency->review_note,
        ];

        if ($toStatus === AgencyStatus::Approved) {
            $updates['approved_at'] = now();
            $updates['approved_by_user_id'] = Auth::id();
        }

        $agency->update($updates);

        if ($fromStatus !== $toStatus) {
            AgencyStatusHistory::create([
                'agency_id' => $agency->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_user_id' => Auth::id(),
            ]);
        }

        return redirect()->route('admin.agencies.show', $agency)->with('status', '審査ステータスを更新しました。');
    }

    public function toggleCollaborationPartner(Agency $agency): RedirectResponse
    {
        $agency->update([
            'is_collaboration_partner' => ! $agency->is_collaboration_partner,
            'collaboration_partner_at' => $agency->is_collaboration_partner ? null : now(),
        ]);

        return redirect()->route('admin.agencies.show', $agency)->with(
            'status',
            $agency->is_collaboration_partner ? '共創パートナーにしました。' : '共創パートナーの指定を解除しました。'
        );
    }

    public function destroy(Agency $agency): RedirectResponse
    {
        if ($agency->inquiries()->exists()) {
            return back()->with('error', 'このパートナーには問い合わせが紐づいているため削除できません。');
        }

        $agency->delete();

        return redirect()->route('admin.agencies.index')->with('status', 'パートナーを削除しました。');
    }

    private function validated(Request $request, ?Agency $agency = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'prefecture' => ['required', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('agencies', 'email')->ignore($agency)],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:10'],
            'bank_branch_name' => ['nullable', 'string', 'max:255'],
            'bank_branch_code' => ['nullable', 'string', 'max:10'],
            'bank_account_type' => ['nullable', Rule::enum(BankAccountType::class)],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
