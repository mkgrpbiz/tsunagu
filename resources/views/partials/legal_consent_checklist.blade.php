<p class="text-xs text-gray-500 mb-2">各文書名をクリックして内容をご確認いただくと、同意のチェックができるようになります。</p>
<label class="flex items-start gap-2 text-sm">
    <input type="checkbox" name="terms_agreed" value="1" required disabled data-agree-checkbox="terms" class="mt-0.5">
    <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="terms">利用規約</button>に同意します</span>
</label>
<label class="flex items-start gap-2 text-sm">
    <input type="checkbox" name="privacy_agreed" value="1" required disabled data-agree-checkbox="privacy" class="mt-0.5">
    <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="privacy">プライバシーポリシー</button>に同意します</span>
</label>
<label class="flex items-start gap-2 text-sm">
    <input type="checkbox" name="partner_agreement_agreed" value="1" required disabled data-agree-checkbox="partner_agreement" class="mt-0.5">
    <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="partner_agreement">パートナー業務委託契約書</button>に同意します</span>
</label>

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
