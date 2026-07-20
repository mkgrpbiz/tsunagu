<?php

namespace App\Http\Controllers\Agency;

use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\LegalDocument;
use App\Models\LegalDocumentConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdditionalInfoController extends Controller
{
    public function edit(): View
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        return view('agency.additional_info.edit', [
            'legalDocuments' => collect(LegalDocumentType::cases())
                ->mapWithKeys(fn (LegalDocumentType $type) => [$type->value => LegalDocument::currentPublished($type)]),
            'typesNeedingConsent' => collect(LegalDocumentType::cases())
                ->filter(fn (LegalDocumentType $type) => $agency->needsConsentFor($type))
                ->map(fn (LegalDocumentType $type) => $type->value)
                ->values(),
            'isReconsent' => $agency->legalDocumentConsents()->exists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $rules = [];

        foreach (LegalDocumentType::cases() as $type) {
            if ($agency->needsConsentFor($type)) {
                $rules["{$type->value}_agreed"] = ['accepted'];
            }
        }

        $request->validate($rules);

        foreach (LegalDocumentType::cases() as $type) {
            if (! $agency->needsConsentFor($type)) {
                continue;
            }

            $document = LegalDocument::currentPublished($type);

            LegalDocumentConsent::create([
                'agency_id' => $agency->id,
                'legal_document_id' => $document->id,
                'consented_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'method' => 'additional_info_submission',
            ]);
        }

        return redirect()->route('agency.home')->with('status', 'ご入力ありがとうございました。');
    }
}
