<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\ReferralCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DepositLinkController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $projectId = $request->query('project_id');
        $q = trim((string) $request->query('q'));

        $projects = $categoryId
            ? Project::where('category_id', $categoryId)->orderBy('name')->get()
            : collect();

        $candidates = collect();
        $selectedProject = $projectId ? $projects->firstWhere('id', (int) $projectId) : null;

        if ($projectId && $q !== '') {
            $candidates = Inquiry::with(['agency', 'lineUser'])
                ->whereDoesntHave('contract')
                ->where('project_id', $projectId)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('name_kana', 'like', "%{$q}%");
                })
                ->latest('inquired_at')
                ->get();
        }

        return view('admin.deposit_links.index', [
            'categories' => Category::orderBy('name')->get(),
            'projects' => $projects,
            'candidates' => $candidates,
            'categoryId' => $categoryId,
            'projectId' => $projectId,
            'q' => $q,
            'selectedProject' => $selectedProject,
        ]);
    }

    public function store(Request $request, Inquiry $inquiry): RedirectResponse
    {
        if ($inquiry->contract) {
            return back()->with('error', 'この問い合わせにはすでに着金が紐付けられています。');
        }

        $data = $request->validate([
            'deposit_date' => ['required', 'date'],
            'deposit_amount' => ['required', 'integer', 'min:0'],
            'agency_reward_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $depositDate = Carbon::parse($data['deposit_date']);
        $paymentDueDate = $depositDate->copy()->addMonthNoOverflow()->day(5);

        $contract = Contract::create([
            'inquiry_id' => $inquiry->id,
            'deposit_date' => $depositDate,
            'deposit_amount' => $data['deposit_amount'],
            'agency_reward_amount' => $data['agency_reward_amount'] ?? $inquiry->project->agency_unit_price,
            'payment_due_date' => $paymentDueDate,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $inquiry->update(['status' => InquiryStatus::Contracted]);

        if ($inquiry->agency->referred_by_agency_id) {
            ReferralCommission::create([
                'contract_id' => $contract->id,
                'referrer_agency_id' => $inquiry->agency->referred_by_agency_id,
                'source_agency_id' => $inquiry->agency_id,
                'amount' => (int) round($contract->agency_reward_amount * 0.1),
                'payment_due_date' => $paymentDueDate,
                'payment_status' => PaymentStatus::Unpaid,
            ]);
        }

        return redirect()
            ->route('admin.deposit-links.index', $request->only(['category_id', 'project_id', 'q']))
            ->with('status', '着金を紐付け、ステータスを着金済みに更新しました。');
    }
}
