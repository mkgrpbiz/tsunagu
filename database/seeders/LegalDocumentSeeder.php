<?php

namespace Database\Seeders;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

// Seeds the initial v1.0 published version of the 3 legal documents.
// The body text below is a placeholder — replace it with the real legal
// text via the admin 契約管理 screen before going live.
// Run manually: php artisan db:seed --class=LegalDocumentSeeder
class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'type' => LegalDocumentType::Terms,
                'title' => 'TSUNAGU 利用規約',
                'body' => "（仮の本文です。実際の利用規約の内容に差し替えてください。）\n\n第1条（適用）\n本規約は、TSUNAGU（以下「本サービス」）の利用条件を定めるものです。",
            ],
            [
                'type' => LegalDocumentType::Privacy,
                'title' => 'TSUNAGU プライバシーポリシー',
                'body' => "（仮の本文です。実際のプライバシーポリシーの内容に差し替えてください。）\n\n第1条（個人情報の取り扱い）\n本サービスは、取得した個人情報を適切に管理します。",
            ],
            [
                'type' => LegalDocumentType::PartnerAgreement,
                'title' => 'TSUNAGU パートナー業務委託契約書',
                'body' => "（仮の本文です。実際のパートナー業務委託契約書の内容に差し替えてください。）\n\n第1条（業務委託）\n運営は、パートナーに対して案件紹介等の業務を委託します。",
            ],
        ];

        foreach ($documents as $document) {
            LegalDocument::create([
                'type' => $document['type'],
                'title' => $document['title'],
                'body' => $document['body'],
                'version' => '1.0',
                'status' => LegalDocumentStatus::Published,
                'effective_date' => now()->toDateString(),
                'published_at' => now(),
                'change_notes' => '初版公開',
            ]);
        }
    }
}
