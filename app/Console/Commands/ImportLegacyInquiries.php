<?php

namespace App\Console\Commands;

use App\Enums\InquiryStatus;
use App\Enums\ProjectStatus;
use App\Models\Agency;
use App\Models\Inquiry;
use App\Models\InviteLink;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Signature('inquiries:import-legacy {path : CSVファイルへのパス} {--dry-run : DBに書き込まず件数だけ確認する}')]
#[Description('過去のパートナー紐付けCSVを問い合わせ(Inquiry)として取り込む（紐付け用のレガシーデータ）')]
class ImportLegacyInquiries extends Command
{
    /**
     * CSVの「案件名」列がこの値の行はインポート対象外としてスキップする。
     */
    private const SKIP_PROJECT_NAMES = [
        '',
        '代表者募集',
        '紹介パートナー登録',
    ];

    /**
     * CSVの「案件名」表記 => 現行projectsの正式名称。
     * Project::findByAnyName() が legacy_names からも解決できるよう、初回実行時に登録する。
     */
    private const PROJECT_NAME_ALIASES = [
        '商品受け取りモニター' => '商品受け取りモニター【毎月1~1.5万円】',
        'DMMFX口座開設' => 'DMM FX口座開設【自己資金なしOK】',
        '覆面調査(即金報酬)' => '覆面調査【即日報酬2,000~3,000円】',
        '美容モニター(BIMONI)' => 'BIMONI【募集モニター30件以上】',
        '税金削減の無料診断' => '税金削減相談【確定申告してない人必見】',
        '不動産売却査定' => '不動産売却査定モニター【実家でもOK】',
        'カードローン' => 'カードローン利用モニター【自己負担なし】',
        '製造業出稼ぎ' => '製造業 出稼ぎ案件｜全国｜短期OK',
        'FX複数開設' => 'FX口座複数開設モニター【最大50,000円】',
        '賃貸併用住宅' => '賃貸併用住宅【おまとめ・支払い削減】',
        '資金調達' => '資金調達相談【ローンでお困りの方向け】',
    ];

    /**
     * 現行projectsに該当がないため、レガシー専用（終了扱い）で新規作成する案件。
     * key = CSVの案件名（= 作成するprojectのnameそのもの）, value = category_id。
     */
    private const LEGACY_ONLY_PROJECTS = [
        'トレード案件' => 1,
        'オールマイティ求人' => 2,
    ];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_readable($path)) {
            $this->error("ファイルが読み込めません: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->applyProjectNameAliases();
        $this->createLegacyOnlyProjects();

        $handle = fopen($path, 'r');
        fgetcsv($handle);

        $imported = 0;
        $skippedProjectName = 0;
        $skippedUnmatchedProject = 0;
        $skippedUnmatchedAgency = 0;
        $unmatchedAgencyCodes = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$timestamp, $code, $projectName, $lineName, $name, $nameKana, $email] = array_pad($row, 7, '');

            $projectName = trim($projectName);
            $code = trim($code);

            if (in_array($projectName, self::SKIP_PROJECT_NAMES, true)) {
                $skippedProjectName++;

                continue;
            }

            $project = Project::findByAnyName($projectName);

            if (! $project) {
                $skippedUnmatchedProject++;
                $this->warn("案件名が一致しません: {$projectName}");

                continue;
            }

            $agency = Agency::where('legacy_code', $code)->first();

            if (! $agency) {
                $skippedUnmatchedAgency++;
                $unmatchedAgencyCodes[$code] = true;

                continue;
            }

            if ($dryRun) {
                $imported++;

                continue;
            }

            $inviteLink = InviteLink::firstOrCreate(
                ['agency_id' => $agency->id, 'project_id' => $project->id],
                ['token' => Str::random(10)],
            );

            Inquiry::create([
                'agency_id' => $agency->id,
                'project_id' => $project->id,
                'invite_link_id' => $inviteLink->id,
                'line_user_id' => null,
                'name' => $name,
                'name_kana' => $nameKana,
                'email' => $email,
                'status' => InquiryStatus::Guided,
                'guidance_sent_at' => null,
                'inquired_at' => Carbon::parse($timestamp),
                'is_legacy_import' => true,
                'legacy_line_display_name' => $lineName,
            ]);

            $imported++;
        }

        fclose($handle);

        $this->info(($dryRun ? '[dry-run] ' : '')."取り込み件数: {$imported}");
        $this->info("スキップ（対象外の案件名）: {$skippedProjectName}");
        $this->info("スキップ（案件名が不明）: {$skippedUnmatchedProject}");
        $this->info("スキップ（紹介コードが不明）: {$skippedUnmatchedAgency}");

        if ($unmatchedAgencyCodes !== []) {
            $this->warn('不明な紹介コード: '.implode(', ', array_keys($unmatchedAgencyCodes)));
        }

        return self::SUCCESS;
    }

    private function applyProjectNameAliases(): void
    {
        foreach (self::PROJECT_NAME_ALIASES as $legacyName => $currentName) {
            $project = Project::where('name', $currentName)->first();

            if (! $project) {
                continue;
            }

            $existing = $project->legacyNamesList();

            if (in_array($legacyName, $existing, true)) {
                continue;
            }

            $existing[] = $legacyName;
            $project->update(['legacy_names' => implode("\n", $existing)]);
        }
    }

    private function createLegacyOnlyProjects(): void
    {
        foreach (self::LEGACY_ONLY_PROJECTS as $name => $categoryId) {
            Project::firstOrCreate(
                ['name' => $name],
                [
                    'category_id' => $categoryId,
                    'status' => ProjectStatus::Closed,
                    'oshigoto_listed' => false,
                    'tsunagu_unit_price' => 0,
                    'agency_unit_price' => 0,
                ],
            );
        }
    }
}
