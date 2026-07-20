@php
    $typesNeedingConsent = $typesNeedingConsent ?? collect(array_keys($legalDocuments->toArray()));
@endphp

<p class="text-xs text-gray-500 mb-2">各文書名をクリックして内容をご確認いただくと、同意のチェックができるようになります。</p>

@foreach ($legalDocuments as $type => $document)
    @if ($typesNeedingConsent->contains($type))
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="{{ $type }}_agreed" value="1" required disabled data-agree-checkbox="{{ $type }}" class="mt-0.5">
            <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="{{ $type }}">{{ \App\Enums\LegalDocumentType::from($type)->label() }}</button>に同意します</span>
        </label>
        @if ($document?->change_notes)
            <p class="text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-md px-3 py-2 mt-1 mb-2" style="white-space: pre-line">今回の変更点: {{ $document->change_notes }}</p>
        @endif
    @else
        <p class="flex items-center gap-2 text-sm text-gray-500">
            <span class="text-xs font-medium border rounded-full px-2 py-0.5 bg-green-50 text-green-700 border-green-200">済</span>
            {{ \App\Enums\LegalDocumentType::from($type)->label() }}
        </p>
    @endif
@endforeach

@foreach ($legalDocuments as $type => $document)
    <div class="hidden" id="legal-content-{{ $type }}" data-title="{{ $document?->title }}">{{ $document?->body }}</div>
@endforeach

<div id="legal-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
    <div id="legal-modal-panel" class="bg-white rounded-lg max-w-lg w-full max-h-[80vh] overflow-y-auto p-6">
        <h3 id="legal-modal-title" class="font-semibold text-lg mb-4"></h3>
        <div id="legal-modal-body" class="text-sm text-gray-700 leading-relaxed" style="white-space: pre-line"></div>
        <button type="button" id="legal-modal-close" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">閉じる</button>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('legal-modal');
    var modalPanel = document.getElementById('legal-modal-panel');
    var modalTitle = document.getElementById('legal-modal-title');
    var modalBody = document.getElementById('legal-modal-body');

    document.querySelectorAll('[data-legal-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var type = button.dataset.legalOpen;
            var source = document.getElementById('legal-content-' + type);

            modalTitle.textContent = source.dataset.title;
            modalBody.textContent = source.textContent;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalPanel.scrollTop = 0;

            var checkbox = document.querySelector('[data-agree-checkbox="' + type + '"]');
            checkbox.disabled = false;
        });
    });

    document.getElementById('legal-modal-close').addEventListener('click', function () {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
})();
</script>
