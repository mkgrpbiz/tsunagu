<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\BankAccountType;
use App\Enums\Gender;
use App\Enums\LineChannel;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyStatusHistory;
use App\Models\LegalDocumentConsent;
use App\Models\NotificationMessageSetting;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $statusCounts = Agency::where('is_system', false)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        $agencies = Agency::withCount(['inquiries', 'referrals'])
            ->with('referredBy')
            ->where('is_system', false)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.agencies.index', [
            'agencies' => $agencies,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'totalCount' => Agency::where('is_system', false)->count(),
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

    public function updateStatus(Request $request, Agency $agency, LineMessagingService $lineMessaging): RedirectResponse
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

            $this->notifyAgencyOfReviewResult($agency, $toStatus, $lineMessaging);
        }

        return redirect()->route('admin.agencies.show', $agency)->with('status', '審査ステータスを更新しました。');
    }

    private function notifyAgencyOfReviewResult(Agency $agency, AgencyStatus $toStatus, LineMessagingService $lineMessaging): void
    {
        if (! $agency->line_uid) {
            return;
        }

        $setting = NotificationMessageSetting::forFeature(
            NotificationMessageSetting::FEATURE_AGENCY_REVIEW,
            'パートナー登録の審査が完了し、承認となりました。案件一覧など各種機能がご利用いただけます。',
            'パートナー登録の審査が完了し、今回は承認を見送らせていただきました。',
        );

        $message = match ($toStatus) {
            AgencyStatus::Approved => $setting->approved_message,
            AgencyStatus::Rejected => $setting->rejected_message,
            default => null,
        };

        if ($message) {
            $lineMessaging->sendPush(LineChannel::Partner, $agency->line_uid, $message);
        }
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

    public function impersonate(Request $request, Agency $agency): RedirectResponse
    {
        Auth::guard('agency')->login($agency);
        $request->session()->regenerate();

        return redirect()->route('agency.home')->with('status', "{$agency->name} としてログインしました。");
    }

    public function bulkPreview(Request $request): View
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        return view('admin.agencies.bulk_preview', [
            'pastedText' => $data['pasted_text'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pasted_text' => ['required', 'string'],
        ]);

        $result = $this->parseBulkText($data['pasted_text']);

        $createdIdsByLegacyCode = [];
        $createdCount = 0;

        foreach ($result['valid'] as $row) {
            $referredByAgencyId = $row['referrer_agency']?->id
                ?? ($row['referral_code'] !== '' ? ($createdIdsByLegacyCode[$row['referral_code']] ?? null) : null);

            $agency = Agency::create([
                'name' => $row['name'],
                'name_kana' => $row['name_kana'],
                'gender' => Gender::Other,
                'prefecture' => $row['prefecture'],
                'occupation' => $row['occupation'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'password' => 'pass1234',
                'must_change_password' => true,
                'status' => AgencyStatus::Approved,
                'approved_at' => now(),
                'approved_by_user_id' => Auth::id(),
                'line_display_name' => $row['line_display_name'],
                'current_activity' => $row['current_activity'],
                'referred_by_agency_id' => $referredByAgencyId,
                ...($row['legacy_code'] ? ['legacy_code' => $row['legacy_code']] : []),
            ]);

            if ($row['timestamp']) {
                $agency->forceFill(['created_at' => $row['timestamp'], 'updated_at' => $row['timestamp']])->save();
            }

            AgencyStatusHistory::create([
                'agency_id' => $agency->id,
                'from_status' => null,
                'to_status' => AgencyStatus::Approved,
                'changed_by_user_id' => Auth::id(),
            ]);

            if ($row['legacy_code']) {
                $createdIdsByLegacyCode[$row['legacy_code']] = $agency->id;
            }

            $createdCount++;
        }

        $invalidCount = count($result['invalid']);
        $status = "{$createdCount}件のパートナーを追加しました。";
        if ($invalidCount > 0) {
            $status .= "{$invalidCount}件はエラーのためスキップしました。";
        }

        return redirect()->route('admin.agencies.index')->with('status', $status);
    }

    /**
     * @return array{valid: array<int, array<string, mixed>>, invalid: array<int, array<string, mixed>>}
     */
    private function parseBulkText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $valid = [];
        $invalid = [];
        $seenEmails = [];
        $seenLegacyCodes = [];
        $batchLegacyCodes = [];

        foreach ($lines as $lineText) {
            if (trim($lineText) === '') {
                continue;
            }

            $columns = explode("\t", $lineText);
            $timestampRaw = trim($columns[0] ?? '');

            if ($timestampRaw === 'タイムスタンプ') {
                continue;
            }

            $legacyCode = trim($columns[1] ?? '');
            $referralCode = trim($columns[2] ?? '');
            $lineDisplayName = trim($columns[3] ?? '');
            $name = trim($columns[4] ?? '');
            $nameKana = trim($columns[5] ?? '');
            $prefecture = trim($columns[6] ?? '');
            $occupation = trim($columns[7] ?? '');
            $currentActivity = trim($columns[8] ?? '');
            $phone = trim($columns[9] ?? '');
            $email = trim($columns[10] ?? '');

            $errors = [];

            if ($name === '') {
                $errors[] = 'お名前が空です';
            }
            if ($nameKana === '') {
                $errors[] = 'フリガナが空です';
            }
            if ($prefecture === '') {
                $errors[] = '都道府県が空です';
            }
            if ($phone === '') {
                $errors[] = '電話番号が空です';
            }

            if ($email === '') {
                $errors[] = 'メールアドレスが空です';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'メールアドレスの形式が不正です';
            } elseif (Agency::where('email', $email)->exists()) {
                $errors[] = '既に登録済みのメールアドレスです';
            } elseif (isset($seenEmails[$email])) {
                $errors[] = '貼り付け内でメールアドレスが重複しています';
            }

            if ($legacyCode !== '') {
                if (Agency::where('legacy_code', $legacyCode)->exists()) {
                    $errors[] = "本人コード「{$legacyCode}」は既に使われています";
                } elseif (isset($seenLegacyCodes[$legacyCode])) {
                    $errors[] = "本人コード「{$legacyCode}」が貼り付け内で重複しています";
                }
            }

            $referrerAgency = $referralCode !== '' ? Agency::where('legacy_code', $referralCode)->first() : null;
            $referrerInBatch = ! $referrerAgency && $referralCode !== '' && isset($batchLegacyCodes[$referralCode]);

            $timestamp = null;
            if ($timestampRaw !== '') {
                try {
                    $timestamp = Carbon::parse($timestampRaw);
                } catch (\Throwable) {
                    $timestamp = null;
                }
            }

            $row = [
                'raw' => $lineText,
                'timestamp' => $timestamp,
                'legacy_code' => $legacyCode !== '' ? $legacyCode : null,
                'referral_code' => $referralCode,
                'referrer_agency' => $referrerAgency,
                'referrer_in_batch' => $referrerInBatch,
                'line_display_name' => $lineDisplayName !== '' ? $lineDisplayName : null,
                'name' => $name,
                'name_kana' => $nameKana,
                'prefecture' => $prefecture,
                'occupation' => $occupation !== '' ? $occupation : null,
                'current_activity' => $currentActivity !== '' ? $currentActivity : null,
                'phone' => $phone,
                'email' => $email,
                'errors' => $errors,
            ];

            if (empty($errors)) {
                if ($email !== '') {
                    $seenEmails[$email] = true;
                }
                if ($legacyCode !== '') {
                    $seenLegacyCodes[$legacyCode] = true;
                    $batchLegacyCodes[$legacyCode] = true;
                }
                $valid[] = $row;
            } else {
                $invalid[] = $row;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
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
