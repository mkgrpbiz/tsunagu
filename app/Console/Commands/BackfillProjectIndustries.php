<?php

namespace App\Console\Commands;

use App\Models\Industry;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('industries:backfill')]
#[Description('求人カテゴリー（has_job_fields=true）の既存案件に業種カテゴリー(industry_id)を一括割り当てする')]
class BackfillProjectIndustries extends Command
{
    /**
     * 最も具体的なルールから先に判定する（最初にマッチした業種を採用）。
     */
    private const RULES = [
        '保育士・保育施設' => '/保育士|保育所|保育室/u',
        '看護師・医療専門職（理学療法士/作業療法士/言語聴覚士等）' => '/看護師|准看護師|正看護師|訪問看護|理学療法士|作業療法士|言語聴覚士|視能訓練士|柔道整復師|管理栄養士|臨床検査技師|臨床工学技士|診療放射線技師|保健師|エンブリオロジスト|胚培養士|獣医師/u',
        '介護・福祉（介護士/ケアマネ/施設運営）' => '/介護|ケアマネ|デイサービス|老人ホーム|ホーム長|生活相談員|施設長|居宅介護|グループホーム|訪問介護|ヘルスケアセンター/u',
        '通信・携帯販売（スマホアドバイザー等）' => '/スマホアドバイザー|販売クルー|携帯|通信・キャリア|光回線/u',
        '美容・エステ・クリニックカウンセラー' => '/美容師|スタイリスト|育毛|サロン|脱毛|クリニックカウンセラー|カウンセラー|葬祭/u',
        'コールセンター・カスタマーサポート' => '/コールセンター|カスタマーサポート|カスタマーサクセス|監視センター管制員|センター運営|オペレーター職/u',
        '製造・工場（組立/加工/検査/ピッキング）' => '/製造|組立|加工|検査業務|ピッキング|フォークリフト|部品|機械オペレーター|工場|バリ取り|梱包|リフト作業|研磨|原料製造|電子機械/u',
        '建設・施工管理・積算' => '/施工管理|積算|建設プロジェクト|現場リーダー|フィールドマネージャー/u',
        '販売・店舗スタッフ（アパレル/ジュエリー/買取等）' => '/販売スタッフ|ストアスタッフ|ジュエリー販売|買取スタッフ|店舗スタッフ|トレーディングカード/u',
        '飲食（ホール/キッチン/店長）' => '/ホールスタッフ|キッチンスタッフ|料理長|店長候補|飲食店/u',
        '運送・ドライバー・配送' => '/タクシードライバー|配送|ドライバー|サービスドライバー|自動車整備士|輸入自動車/u',
        'ブライダル・ウエディング' => '/ウエディングプランナー|ブライダル/u',
        '教育（学習塾/教員等）' => '/学習塾|講師|教室長|高等学校の教員|高校教員/u',
        '不動産（営業/仲介/管理/仕入）' => '/不動産|マンション管理フロント|住宅営業|住宅プランナー|用地仕入|投資用不動産|賃貸住宅アドバイザー|売買仲介/u',
        '士業事務所サポート（弁護士/司法書士）' => '/弁護士|司法書士|相談者の方のお悩み/u',
        'ペット関連' => '/子犬|子猫|ペット/u',
        'IT・Web・エンジニア・データ系' => '/CAD|データサイエンティスト|データ分析|エンジニア|Webディレクター|WEBマーケター|Webアプリ|ソフトウェア|システム導入|システム監査|FAE職|デジタルマーケ|広告運用|マーケティングプランナー|マーケティングコンサル|ビジネスデザイナー|サービスデザイナー|動画クリエイター|ヘッドホンの開発|医療機器開発|開発評価/u',
        '人事・労務・採用' => '/人事|労務|新卒採用/u',
        '経理・財務・経営管理' => '/経理|財務|経営管理|販管経理/u',
        '経営・企画・マネジメント（CEO/事業企画等）' => '/CEO室|事業責任者|経営企画|事業企画|事業開発部|経営に携わ|事業マネジメント|QA組織マネージャー|プロジェクトマネジメント職|社長秘書/u',
        '法務' => '/法務/u',
        '営業（人材/電力/法人/個人営業等）' => '/キャリアアドバイザー|リクルーティング|コンサル営業|法人営業|新規開拓|電力|薬局向け|営業職|セールス|営業マネジャー|個人営業|反響営業|ソリューション営業|営業サポート|営業企画|営業所長|ルート営業|人材営業|配置薬|肥料・農業資材|太陽光パネル|キャリアパートナー/u',
        '事務・バックオフィス（一般事務/総務等）' => '/事務|財務|オフィスワーク|総務|受注管理/u',
        '製薬・医療機関管理職（医事課等）' => '/医事課|製薬メーカー/u',
    ];

    private const OTHER = 'その他';

    public function handle(): int
    {
        $projects = Project::whereHas('category', fn ($q) => $q->where('has_job_fields', true))
            ->whereNull('industry_id')
            ->get(['id', 'name', 'job_description', 'qualifications']);

        $matchedNames = [];
        foreach ($projects as $project) {
            $matchedNames[$project->id] = $this->matchIndustryName($project);
        }

        $counts = array_count_values($matchedNames);
        arsort($counts);

        // 件数降順の順でIndustryを作成（既存業種はsort_orderを変更しない）
        $sortOrder = 0;
        $industryIds = [];
        foreach (array_keys($counts) as $name) {
            $sortOrder++;
            $industry = Industry::firstOrCreate(['name' => $name], ['sort_order' => $sortOrder]);
            $industryIds[$name] = $industry->id;
        }

        foreach ($matchedNames as $projectId => $name) {
            Project::where('id', $projectId)->update(['industry_id' => $industryIds[$name]]);
        }

        $this->info('対象案件数: '.$projects->count());
        foreach ($counts as $name => $count) {
            $this->line("{$name}: {$count}件");
        }

        return self::SUCCESS;
    }

    private function matchIndustryName(Project $project): string
    {
        $text = $project->name.' '.$project->job_description.' '.$project->qualifications;

        foreach (self::RULES as $name => $pattern) {
            if (preg_match($pattern, $text)) {
                return $name;
            }
        }

        return self::OTHER;
    }
}
