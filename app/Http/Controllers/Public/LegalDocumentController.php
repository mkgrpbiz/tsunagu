<?php

namespace App\Http\Controllers\Public;

use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function show(string $type): View
    {
        $document = LegalDocument::currentPublished(LegalDocumentType::from($type));

        abort_if(! $document, 404);

        return view('public.legal_documents.show', [
            'document' => $document,
        ]);
    }
}
