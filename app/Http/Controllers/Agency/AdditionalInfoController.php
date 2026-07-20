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
        return view('agency.additional_info.edit', [
            'legalDocuments' => collect(LegalDocumentType::cases())
                ->mapWithKeys(fn (LegalDocumentType $type) => [$type->value => LegalDocument::currentPublished($type)]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $request->validate([
            'terms_agreed' => ['accepted'],
            'privacy_agreed' => ['accepted'],
            'partner_agreement_agreed' => ['accepted'],
        ]);

        $consentedDocumentIds = $agency->legalDocumentConsents()->pluck('legal_document_id');

        foreach (LegalDocumentType::cases() as $type) {
            $document = LegalDocument::currentPublished($type);

            if (! $document || $consentedDocumentIds->contains($document->id)) {
                continue;
            }

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
