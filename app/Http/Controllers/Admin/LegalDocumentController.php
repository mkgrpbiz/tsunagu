<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function index(): View
    {
        $documents = collect(LegalDocumentType::cases())->map(fn (LegalDocumentType $type) => [
            'type' => $type,
            'latest' => LegalDocument::latestVersion($type),
        ]);

        return view('admin.legal_documents.index', [
            'documents' => $documents,
        ]);
    }

    public function edit(string $type): View
    {
        $type = LegalDocumentType::from($type);
        $latest = LegalDocument::latestVersion($type);

        return view('admin.legal_documents.edit', [
            'type' => $type,
            'document' => $latest,
            'statuses' => LegalDocumentStatus::cases(),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $type = LegalDocumentType::from($type);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'version' => ['required', 'string', 'max:20'],
            'effective_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(LegalDocumentStatus::class)],
            'change_notes' => ['nullable', 'string'],
            'requires_reconsent' => ['nullable', 'boolean'],
        ]);

        if ($data['status'] === LegalDocumentStatus::Published->value) {
            LegalDocument::where('type', $type)
                ->where('status', LegalDocumentStatus::Published)
                ->update(['status' => LegalDocumentStatus::Unpublished]);
        }

        LegalDocument::create([
            'type' => $type,
            'title' => $data['title'],
            'body' => $data['body'],
            'version' => $data['version'],
            'status' => $data['status'],
            'effective_date' => $data['effective_date'],
            'change_notes' => $data['change_notes'] ?? null,
            'requires_reconsent' => $request->boolean('requires_reconsent'),
            'published_at' => $data['status'] === LegalDocumentStatus::Published->value ? now() : null,
            'created_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('admin.legal-documents.index')->with('status', '契約文書を保存しました。');
    }

    public function history(string $type): View
    {
        $type = LegalDocumentType::from($type);

        return view('admin.legal_documents.history', [
            'type' => $type,
            'documents' => LegalDocument::where('type', $type)
                ->with('createdByUser')
                ->latest('created_at')
                ->get(),
        ]);
    }
}
