<?php

namespace App\Http\Controllers\Public;

use App\Enums\ActivityType;
use App\Enums\AgencyStatus;
use App\Enums\BankAccountType;
use App\Enums\Gender;
use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyStatusHistory;
use App\Models\HomeBlock;
use App\Models\HomePageContent;
use App\Models\LandingPageContent;
use App\Models\LegalDocument;
use App\Models\LegalDocumentConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgencyRegistrationController extends Controller
{
    public function landing(Request $request): View
    {
        $lpContent = LandingPageContent::current();

        return view('public.agency_register.landing', [
            'referralCode' => $request->query('ref'),
            'benefitsBlock' => new HomeBlock([
                'type' => 'benefits',
                'title' => $lpContent->benefits_title,
                'body' => $lpContent->benefits_body,
            ]),
            'lpContent' => $lpContent,
            'homeContent' => HomePageContent::current(),
        ]);
    }

    public function form(Request $request): View
    {
        return view('public.agency_register.form', [
            'genders' => Gender::cases(),
            'bankAccountTypes' => BankAccountType::cases(),
            'activityTypes' => ActivityType::cases(),
            'desiredActivityOptions' => Agency::DESIRED_ACTIVITY_OPTIONS,
            'referralCode' => $request->query('ref'),
            'legalDocuments' => collect(LegalDocumentType::cases())
                ->mapWithKeys(fn (LegalDocumentType $type) => [$type->value => LegalDocument::currentPublished($type)]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'prefecture' => ['required', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:agencies,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch_name' => ['nullable', 'string', 'max:255'],
            'bank_account_type' => ['nullable', Rule::enum(BankAccountType::class)],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'referral_code' => ['nullable', 'string', 'max:20'],
            'activity_type' => ['required', Rule::enum(ActivityType::class)],
            'company_name' => [
                Rule::requiredIf($request->input('activity_type') === ActivityType::Corporation->value),
                'nullable', 'string', 'max:255',
            ],
            'desired_activities' => ['required', 'array', 'min:1'],
            'desired_activities.*' => ['string', Rule::in(Agency::DESIRED_ACTIVITY_OPTIONS)],
            'current_activity' => ['required', 'string'],
            'track_record' => ['nullable', 'string'],
            'media_urls' => ['nullable', 'string'],
            'self_pr' => ['nullable', 'string'],
            'terms_agreed' => ['accepted'],
            'privacy_agreed' => ['accepted'],
            'partner_agreement_agreed' => ['accepted'],
        ]);

        $referrer = null;

        if (filled($data['referral_code'] ?? null)) {
            $referrerId = (int) preg_replace('/\D/', '', $data['referral_code']);
            $referrer = Agency::find($referrerId);

            if (! $referrer) {
                throw ValidationException::withMessages([
                    'referral_code' => '紹介コードが正しくありません。',
                ]);
            }
        }

        unset($data['referral_code'], $data['terms_agreed'], $data['privacy_agreed'], $data['partner_agreement_agreed']);
        $data['must_change_password'] = false;
        $data['referred_by_agency_id'] = $referrer?->id;
        $data['status'] = AgencyStatus::Pending;

        $agency = Agency::create($data);

        AgencyStatusHistory::create([
            'agency_id' => $agency->id,
            'from_status' => null,
            'to_status' => AgencyStatus::Pending,
            'changed_by_user_id' => null,
        ]);

        foreach (LegalDocumentType::cases() as $type) {
            $document = LegalDocument::currentPublished($type);

            if ($document) {
                LegalDocumentConsent::create([
                    'agency_id' => $agency->id,
                    'legal_document_id' => $document->id,
                    'consented_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'method' => 'web_registration',
                ]);
            }
        }

        Auth::guard('agency')->login($agency);

        return redirect()->route('agency.home')->with(
            'status',
            "登録申請を受け付けました。\n運営による審査完了後、案件一覧および各種機能をご利用いただけます。"
        );
    }
}
