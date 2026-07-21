# TSUNAGU 仕様まとめ（2026-07-22時点）

パートナー（旧・代理店）が案件を紹介し、紹介報酬・共創報酬を得られる審査制のビジネスプラットフォーム。Laravel 13 + Blade + SQLite（本番/STGはMySQL）。GitHub（`https://github.com/mkgrpbiz/tsunagu.git`, `main`ブランチ）で管理し、STG（`https://stg-tsunagu.mkgrp.biz`、Xserver `sv16576.xserver.jp`）へ`git pull`でデプロイ。本番ドメインは`tsunagu.mkgrp.biz`を予定（未稼働）。

## 用語

- 「パートナー」= 旧「代理店」「紹介パートナー」。`Agency`モデル・`agencies`テーブル・ルート名（`agency.*`, `admin.agencies.*`）などコード上の命名は`agency`のまま変更していない。UI表示文言のみ「パートナー」に統一。
- 「共創パートナー」= 旧「協業パートナー」。案件・ビジネスを持ち込む形で関わるパートナーの呼称。

## 認証・ガード

- `web`ガード: 管理者。`User`モデル（`role`: `admin`/`operator`、`accessible_menus`: json配列）
- `agency`ガード: パートナー。`Agency`モデル

## 収益の仕組み（2系統、互いに独立）

1. **パートナー紹介コミッション（10%）** — `Admin\DepositLinkController::store()`
   `$inquiry->agency->referred_by_agency_id`が設定されている場合、その紹介元パートナーに`agency_reward_amount`の10%を`ReferralCommission`として計上。登録時の`?ref=B0001`形式の紹介コードで作られる`referred_by_agency_id`（自己参照FK）だけで駆動する。
2. **共創報酬（利益の30%）** — `Admin\CollaborationRewardController::index()`
   `Project.client_name`ごとに、`Project.referrer_agency_id`が設定されていればそのパートナーに対し「入金合計 − パートナー報酬合計（＝利益）」の30%を`CollaborationReward`として計算・登録。`Project`編集画面で管理者が手動設定する。

**`Agency.is_collaboration_partner`（共創パートナータグ）は上記どちらのロジックにも影響しない。** 管理画面上の区分・専用一覧ページ表示・案件の紹介者プルダウンの絞り込み用途のみに使う独立フラグ（ユーザー確認済み）。

## 審査制（クローズドパートナープラットフォーム）

- 新規登録者は自動的に`AgencyStatus::Pending`（審査中）。`approved`になるまで案件一覧・パートナー紹介・共創パートナー申請は使えない。
  - ページ単位のブロック: `EnsureAgencyApproved`ミドルウェア（alias `agency.approved`）→ 403 + `agency.restricted`ビュー
  - カード単位のブロック: `Agency\HomeController`が`$restrictedReason`（`pending_review`/`consent_required`/null）を計算し、`partials/home_block.blade.php`内でグレーアウト表示・理由別メッセージ出し分け（実際の紹介URL・コードはHTMLに出力しない）
- 管理画面の「パートナー」詳細画面で承認/否認/利用停止/審査中へ戻す操作、`AgencyStatusHistory`に遷移履歴を記録
- 既存パートナーは移行時に一律`approved`済み（審査中に巻き込まれない）
- 契約同意（3文書）が未提出の代理店（既存パートナー等）は`EnsureAgencyConsentsSubmitted`ミドルウェア（alias `agency.consents_submitted`）→ `agency.additional-info.edit`へリダイレクト。ホームの2カードも同様にグレーアウト（`consent_required`理由）

### 法的文書のバージョン管理（契約管理）

- `legal_documents`テーブルは**1行=1バージョンの追記専用台帳**（既存行はUPDATEしない、常にINSERT）。新版を`published`にすると同じ`type`の旧`published`行を`unpublished`に変更。
- `type`: `terms`（利用規約）/ `privacy`（プライバシーポリシー）/ `partner_agreement`（パートナー業務委託契約書）
- 登録フォームでは各文書をモーダル表示。**開いて内容を確認するまで同意チェックボックスがdisabled**（クリックできない）
- 同意時に`LegalDocumentConsent`へ IP・User-Agent・日時・同意方法・同意したバージョンを記録

## 管理者権限（BIMONI方式を移植、サーバー側チェックを追加）

- `User.role`: `admin`（全メニュー）/ `operator`（`accessible_menus`で許可されたメニューのみ）
- `EnsureCanAccessMenu`ミドルウェア（alias `menu:<key>`）で**ルート単位でもサーバー側チェック**（BIMONI本家はナビ非表示のみでサーバー側チェックがないため、そこはTSUNAGUで独自に強化した点）
- メニューキー: `dashboard` / `projects` / `categories` / `agencies` / `collaboration_partners` / `inquiries` / `deposit_links` / `payments` / `announcements` / `collaboration_referrals` / `collaboration_rewards` / `legal_documents` / `home` / `landing_page_content`
- 「管理者」画面自体はメニューキーではなく`User::isAdmin()`のみでガード（operatorには一切見せない）

## 登録フォーム（`public/agency_register/form.blade.php`）

- 活動区分: 個人 / 個人事業主 / 法人（「その他」は削除済み）
  - 個人事業主を選択 → 「屋号名（任意）」欄が表示
  - 法人を選択 → 「法人名」欄が必須表示（**サーバー側でも**`Rule::requiredIf`でバリデーション、JS任せにしていない）
  - どちらも`Agency.company_name`に保存（パートナー一覧・共創パートナー一覧の「会社名」列で表示）
- `?ref=`付きリンクからの登録は紹介コード欄が読み取り専用になり「（任意）」表記が消える

## 案件（Project）の紹介者・単価・取引先

- `Project.referrer_agency_id`の選択肢は**`is_collaboration_partner=true`のパートナーのみ**（検索ボックス付きプルダウン、外部ライブラリなしのvanilla JS）
- 編集画面では、既に設定されている紹介者が共創パートナーでなくなっていても選択肢から消えないよう救済表示する
- **単価は複数パターン対応**（2026-07-22）: `tsunagu_unit_price`/`agency_unit_price`（単一integer）を廃止し、`tsunagu_unit_prices`/`agency_unit_prices`（JSON配列、`array`キャスト）に変更。案件編集で「+」により金額を何個でも追加可能（ラベルは付けない、単純に複数の金額パターン）。`Project::singleTsunaguUnitPrice()`/`singleAgencyUnitPrice()`はパターンが1個の時だけその値を返す（着金紐付け画面の単価入力欄の自動プリフィル用、パターンが2個以上や変動制の場合はnullを返し手入力を促す）
- `Project.is_recurring`（ストック系案件）・`Project.bulk_link_enabled`（一括紐付け対象）は着金紐付けセクション参照
- **取引先名（`client_name`）はdatalistでサジェスト**（2026-07-22）: 既存の全案件から使われている`client_name`の一覧（`Project::whereNotNull('client_name')->distinct()`）を`<datalist>`として表示し、既存の取引先を選びやすくする一方、新規取引先の自由入力もそのまま可能（サーバー側で候補以外を拒否するような強制はしていない）。共創報酬の集計が`client_name`の文字列完全一致に依存しているため、表記ゆれ防止が目的

## 一覧画面の列構成

- パートナー一覧（`admin/agencies/index`）: 会社名 / 名前 / フリガナ / 審査ステータス / 登録申請日時 / 承認日時 / 問い合わせ数 / パートナー紹介数
- 共創パートナー一覧（`admin/collaboration-partners/index`）: 会社名 / 名前 / フリガナ / 公開案件数 / 詳細を表示

## ダッシュボード（`admin/dashboard`）

- 月次・累計の切替、カード下は前月比（差分＋％、累計モードのみ累計表示にフォールバック）
- 指標: 紹介パートナー数（→表示は「パートナー数」）、共創パートナー数、問い合わせ数、着金数、売上、支払い、利益
- 折れ線グラフ2種（パートナー数×問い合わせ数、売上×利益）、外部チャートライブラリなしの自前SVG実装（`partials/line_chart.blade.php`）

## ホーム・LP編集機能

管理画面ナビに「ホーム」「LP」の2つの編集画面がある。

- **ホーム**（`admin/home-content`、ログイン後のパートナー向けホーム画面用）: `HomePageContent`シングルトン行。ヘッダータグライン、締めのメッセージ、ロゴ画像
- **LP**（`admin/landing-page-content`、`/agency/register`の未ログイン向け招待ページ用）: `LandingPageContent`シングルトン行。タグライン、見出し（1行目/強調部分/続き）、メリット（見出し＋`タイトル|説明`形式の項目一覧、`HomeBlock`とは分離管理）、ご参加の流れ（見出し＋ステップ3つ）、登録ボタンのテキスト、ロゴ上のバッジテキスト
- **ロゴ画像**: `HomePageContent.brand_logo_path`が実体で、ホーム・LP両方の編集画面から共通でアップロード/削除できる（同じ1枚を共有）。未設定時は「TSUNAGU Partner Network」の文字表示にフォールバック
- 画像は`storage/app/public/brand/`に保存（`php artisan storage:link`済み）

## パートナーのコード体系（`legacy_code`）

- 過去のスプレッドシート運用時代のパートナーコード（`B0001`形式）を`agencies.legacy_code`（unique）に保持し、**これが正式な会員番号・紹介コードとして使われ続ける**。`Agency::getReferralCodeAttribute()`は`legacy_code ?: sprintf('B%04d', $this->id)`
- 新規登録者には`Agency::generateUniqueLegacyCode(int $startFrom)`が衝突チェックしながら自動採番（`booted()`の`created`フックで自動実行）。過去データの穴あき番号と衝突しないよう毎回`legacy_code`テーブルを検索する
- `legacy_referral_code`は紹介元コードの歴史的参照用（unique制約なし、ロジックには使わない）
- 管理画面の列名は「会員番号」「紹介者」（本人コード／紹介コードという旧称からリネーム済み）。「紹介者」列には紹介元の**会員番号**を表示（名前ではない）
- 2026-07に旧スプレッドシート（247件）から一括インポート済み。招待リンク以外の旧問い合わせデータを後日インポートする計画があり、そのため`Project.legacy_names`（複数可・改行区切り）と`Project::findByAnyName(string $name)`ヘルパーを用意済み（現行案件名と旧データ表記のどちらでも一致させるため。まだどのインポート処理からも呼ばれていない、将来のインポート作業用の下地）

## パートナー銀行情報の入力（全銀検索）

- BIMONI（`C:\laragon\www\bimoni`）と同じ全銀データ（`resources/js/data/banks.json`、`public/data/zengin/branches/{bank_code}.json`）とオートコンプリートJS（`resources/js/bank-autocomplete.js`）を移植
- `agencies.bank_code` / `bank_branch_code`に選択結果を保存（銀行名・支店名の文字列自体は別カラムのまま）

## 案件・カテゴリーの並び替え

- `categories.sort_order` / `projects.sort_order`（いずれもunsigned int、既存データは元の表示順で自動採番済み）
- 管理画面の一覧はネイティブJS（外部ライブラリなし）のドラッグ＆ドロップで並び替え可能。カテゴリーは常時、案件は「特定カテゴリーを選択」かつステータス「すべて」表示時のみ（`Admin\ProjectController::index()`の`$canReorder`）
- この並び順はパートナー向け案件一覧（`Agency\ProjectController`）・公開おしごとナビ（`Public\OshigotoController`）の表示順にもそのまま反映される（`categories.sort_order`→`projects.sort_order`の順でJOIN・ORDER BY）

## 着金紐付け（`admin/deposit-links`）

- 検索欄（名前・フリガナ・LINE名）単独で検索可能。カテゴリー・案件名は絞り込み専用のオプション項目（以前は「カテゴリー→案件→検索」の3段階が必須だったのを解消）
- 候補カードは2行表示: 上段（`bg-blue-50`）に問い合わせ日時・パートナー・案件名・LINE名・名前・フリガナ、下段にTSUNAGU単価/パートナー単価（両方とも編集可能な入力欄。案件に単価パターンが1つだけならその値を自動プリフィル、複数パターンや変動制なら空欄で手入力必須）・件数・TSUNAGU合計/パートナー合計（自動計算・readonly）・TSUNAGU利益（表示のみ）・紐付けボタン
- **合計金額はサーバー側で単価×件数から再計算する**（クライアントが送ってきた合計値は信用しない）
- 「+ もう1パターン追加」で1回の紐付け送信に複数の（単価/単価/件数）ラインを追加可能。各ラインが個別の`Contract`になる（`ContractLinkingService::linkInquiry()`が配列で受け取り、ライン数分`Contract::create()`をループ）
- **ストック系案件**（`Project.is_recurring`）: 案件編集のチェックボックスで有効化すると、同じ問い合わせに何度でも着金紐付けができる（`Inquiry.contract`のUNIQUE制約撤廃が前提）。OFFの案件は1回紐付けると候補から消える（誤って二重に紐付けるのを防止）
  - `Inquiry::contract(): HasOne`は`->latestOfMany()`を使うが、**`whereDoesntHave('contract')`は`ofMany`リレーションに対して正しく動かない**（Laravel既知の制限）。既存契約の有無を判定する検索条件には必ず素の`Inquiry::contracts(): HasMany`を使うこと
- **一括紐付け（貼り付け）**: `<details>`アコーディオン内に配置。案件を1つ選択（`bulk_link_enabled=true`の案件のみプルダウンに表示、案件編集の専用チェックボックスで対象を絞る）→ テキストエリアに「名前 フリガナ TSUNAGU単価 パートナー単価 件数（省略可、未入力は1件）」をタブまたは半角スペース2個以上区切りで貼り付け（`preg_split('/\t+| {2,}/', ...)`。単語内の単一スペース、例:「山田 太郎」は区切りとして扱わない）→ プレビュー画面（`admin.deposit-links.bulk-preview`）で一致/不一致を確認 →確定（`bulk-store`）
  - マッチングは「同一案件×名前（フリガナ指定時はそれも一致）」で候補問い合わせを検索し、`whereDoesntHave('contracts')->orWhereHas('project', fn ($q) => $q->where('is_recurring', true))`で絞り込み、`inquired_at`昇順で先着一致・同一バッチ内の二重取得なしという単純なルールで割り当てる
- **該当なし成果**: 「該当する問い合わせ候補」の検索結果画面から「該当なし成果」ボタンでインライン展開されるフォーム（案件・名前・フリガナ・TSUNAGU単価・件数のみ入力、パートナー単価は常に0＝全額TSUNAGU利益）。紹介元パートナーが存在しない成果（直接反響など）を表すため、`Agency::noReferralAgency()`（`is_system=true`の専用ダミーAgency、`firstOrCreate`で1件だけ作られるシングルトン）に紐付ける。ダミーAgencyは`admin/agencies`一覧・件数集計から除外（`Agency::where('is_system', false)`）
- `ContractLinkingService::linkInquiry(Inquiry $inquiry, array $lines): bool`が着金紐付け・該当なし成果・合計成果反映（後述）で共用する中心ロジック。ライン単位で`Contract`作成（`deposit_date`=当日固定、`payment_due_date`=当月末締め翌月5日）＋`agency_reward_amount`に`agency_unit_price`・`count`も併せて保存（パートナー着金・支払いページの単価/件数列表示用）＋紹介元パートナーへの10%`ReferralCommission`自動計上。ライン単位で`apply_referral_commission`（省略時true）を指定すればこの10%計上を個別にスキップできる（後述の合計成果反映で使用）

## 合計成果反映（`admin/aggregate-results`、着金紐付けメニューの直下）

個別の問い合わせ（Inquiry）に紐づけずに、実在するパートナーへ成果をまとめて計上するための画面。BIMONIに直接ジョイントしている代理店など、経理・契約をTSUNAGU側で処理しているが個別の顧客問い合わせ単位のマッチングが不要なケース向け。

- パートナー検索: 会員番号（`referral_code`、算出プロパティのためDBクエリではなくPHP側でフィルタ）・LINE名・名前・フリガナのいずれかで一致するAgency（`is_system=false`のみ）を検索→選択
- 選択後、案件名（プルダウン）・TSUNAGU単価・パートナー単価・件数のラインを「+」で複数作成可能（各ラインごとに合計をリアルタイム表示、全体合計も表示）
- パートナー10%対象/対象外の切替チェックボックスをライン単位に用意（紹介元パートナーが設定されているAgencyのみ表示。デフォルトは「対象」＝チェック済み、稀に10%なしのケースがあるため対象外にも切替可能）。紹介元がいないAgencyの場合はチェックボックス自体を出さず「対象外（紹介元なし）」の表示のみ
- 送信すると、ラインごとに`Inquiry`（`name`/`name_kana`は固定文言「合計成果反映」、選択した実在Agencyに紐付け）を自動生成し、`ContractLinkingService::linkInquiry()`で着金紐付けと同じ`Contract`作成ロジックを共用
- 選択中パートナーの反映履歴（案件名・TSUNAGU合計・パートナー合計・パートナー10%対象有無・支払状況）をページ下部に表示（`Contract.referralCommission`のHasOneリレーションで判定）
- **`inquiries.is_bulk_reflection`（bool）フラグ**: この画面で作った`Inquiry`は実際の顧客問い合わせではないため、管理画面の通常の「問い合わせ一覧」（`admin/inquiries`）とその件数集計からは除外する（`is_bulk_reflection=false`でフィルタ）。除外しても合計成果反映画面自体の反映履歴では引き続き参照できる

## 問い合わせステータス（`InquiryStatus`）

2026-07-22に整理。現在の4値: `New`（案内待ち）/ `GuidanceFailed`（エラー）/ `Guided`（案内済）/ `Contracted`（着金）。

- **`失注`（Lost）は廃止**した。運用上、失注後の追跡ができておらず不要と判断（廃止前の実データに`lost`ステータスの行は0件だったためデータ移行は不要だった）。管理画面の「失注にする」手動トグルボタン・`toggleLost()`・関連ルートも削除。着金以外は自動遷移のみになった
- **`New`のラベルを「新規」→「案内待ち」に変更**。LINE友だち追加後に案内メッセージ（`Project.line_auto_message`）を自動送信する仕組み（`ApplyController::store()`/`LineWebhookController::handleFollow()`）があり、「新規のまま止まる」の大半は単に「まだLINE友だち追加していない」状態を指すため
- **`GuidanceFailed`（エラー）を新設**: `LineMessagingService::sendPush()`がLINE Push APIの呼び出しに失敗した場合（トークン無効・レート制限・ブロック中・通信エラー等）に`false`を返す。従来はこの戻り値をチェックしておらず、送信に失敗しても気づかれずに「新規」のままサイレントに残っていた（`ApplyController::store()`側は戻り値を全くチェックしておらず、常にGuided扱いにしてしまうバグもここで合わせて修正）。送信失敗時は`GuidanceFailed`に遷移させ、管理画面の問い合わせ一覧でエラー行の横に「再送信」ボタン（`Admin\InquiryController::resendGuidance()`）を表示し、その場で再送信→成功すれば`Guided`に更新できるようにした
- **パートナー向け表示ではエラーを隠す**: `InquiryStatus::partnerLabel()`を追加し、パートナー側の問い合わせ一覧（`agency/inquiries`）では`GuidanceFailed`も`New`と同じ「案内待ち」表示にする（送信エラーは運営側で対応すべき内部事情のため）。管理画面側の`label()`は従来通り「エラー」を表示する
- `status`カラムはDBネイティブのENUM型ではなく単なる`string`のため、ステータスの追加・削除にDBマイグレーションは不要（PHP側のenum定義のみで完結）

## 過去の問い合わせデータのインポート（`inquiries:import-legacy`）

- 用途は**着金紐付け用のマッチングのみ**。パートナー向け画面（`agency/inquiries`）には一切表示しない
- `inquiries`テーブルの`invite_link_id`/`line_user_id`は元々必須FKだが、過去データには実在するLINE UIDも招待リンクも無いためインポートできない → 両カラムをnullable化し、`is_legacy_import`（bool）と`legacy_line_display_name`（生のLINE表示名テキスト）を追加して緩和した
- `invite_link_id`は該当するagency×projectの組み合わせで`InviteLink::firstOrCreate`（本番運用と同じ一意制約に乗る）。`line_user_id`は常にnull
- 案件名の表記ゆれは`Project.legacy_names`（改行区切りテキスト）に別名を登録し、`Project::findByAnyName()`で解決。既存projectと対応が付かない旧案件（トレード案件）は**ステータス`closed`・`oshigoto_listed=false`の専用projectを新規作成**して紐付け（パートナー向け一覧にも案件一覧にも出ない）
- **注意**: 「オールマイティ求人」は当初どのprojectにも対応がないと判断して専用projectを作ったが、実際は管理画面側で既に「製造業 出稼ぎ案件｜全国｜短期OK」の`legacy_names`に登録済みだった。`findByAnyName()`は`name`完全一致を`legacy_names`検索より優先するため、コマンド側の`PROJECT_NAME_ALIASES`に載っていない別名は見落とされる。**別名を追加する前に、対象projectの`legacy_names`を`admin/projects`側で必ず確認すること**（誤って作成した専用projectと11件の問い合わせは本番・ローカルとも製造業出稼ぎ案件へ付け替え済み）
- 「代表者募集」「紹介パートナー登録」など案件として扱う意味がないカテゴリはインポート対象外としてスキップ
- 紹介コード（`legacy_code`）が現行agenciesと一致しない行（実データでは5件）はスキップし、コマンド実行結果に一覧表示。個別確認が必要な場合はそこから追う
- 除外は`Agency\InquiryController`（問い合わせ一覧）のみに適用。`agency/contracts`（着金・支払い）は除外**しない** — レガシー問い合わせに実際の着金が紐付いた（`Contract`が作られた）時点で、それは現在進行系の実支払いなのでパートナーに通常通り表示される
- 実行方法: `php artisan inquiries:import-legacy {CSVパス} [--dry-run]`。同じCSVを再実行すると同じ行がそのまま重複登録される（重複排除はしていない）ため、再実行が必要な場合は要注意

## パートナー着金・支払いページ（`agency/contracts`）

- 3セクション: 紹介報酬（自分の着金、1行=1件、列は**着金日・案件名・名前・フリガナ・単価・件数・合計・支払予定日**。同一案件・同一人物でも単価パターンが違えば`Contract`が別々になるため複数行で表示される＝着金紐付けと同じ粒度）／パートナー10%（紹介先パートナー×支払予定日ごとに件数・合計額を集計した行）／共創パートナー30%（取引先ごとに案件数・着金数・合計額を集計、**承認済みのもののみ**表示）
- `Contract`に`agency_unit_price`・`count`カラムを追加（2026-07-22）し、`ContractLinkingService`がライン作成時に保存。マイグレーション前の既存データはこの2カラムがnullのため「－」表示にフォールバック
- ページ上部に支払いサイクルの案内文（月末締め翌月5日払い、**5日が土日祝の場合は明けの振り込み**、¥1,000未満は繰り越し）
- 「繰り越し報酬」表示は**累計（全期間）の未払い合計が¥1,000未満の場合、その全額**（`Agency::totalPendingPayout()`が0円になるまで自然に繰り越り続ける仕組みで、繰り越し専用のDBカラムは無い）

## 支払い管理（`admin/payments`）と繰り越し予定

2026-07-22に「カテゴリー別内訳テーブル」方式から**パートナー別支払い一覧＋詳細ページ**方式に全面刷新（旧・4ブロック構成の説明は廃止）。

- 一覧（`admin/payments`）: パートナー別に1行（会員番号・紹介報酬・パートナー10%・共創パートナー30%・合計・詳細ボタン）。集計は`Agency::pendingPayoutBreakdown()`
  - **一括CSV抽出**: 振込指定日（デフォルト＝直近の5日、土日を考慮して調整可能な日付ピッカー）を指定し、全銀協形式の総合振込CSVをShift_JISでダウンロード（`ZenginTransferCsvBuilder`/`ZenginNameNormalizer`、BIMONIの本番GASスクリプトのロジックをPHPに移植。氏名の全角→半角カナ変換込み。半角中黒`ｦ-ﾟ`の文字フィルタ範囲が半角中点U+FF65を含まずストリップされる仕様もBIMONI本家と同じ挙動として踏襲）。振込元口座・委託者コード/名は`.env`の`ZENGIN_*`系（`config('services.zengin_transfer')`）
  - **一括で支払済みにする**: 画面上の月フィルタに関係なく、その時点で支払対象になっている全パートナーの未払い分を一括で支払済みに更新
- 詳細ページ（`admin/payments/{agency}`）: 該当パートナーの紹介報酬・パートナー10%・共創パートナー30%の3履歴を表示。**「まとめて支払済みにする」「まとめて未払いに戻す」の一括ボタンのみ**（個別行ごとの支払済み/未払いボタンはUIから削除済み。ルート・コントローラーメソッド自体は保守的に残してある＝UIから参照されていないだけ）
- `CollaborationReward`にも`payment_status`/`payment_due_date`/`paid_at`があり、他の2種と同じ支払済み/未払いの管理ができる（承認待ち/承認の`status`とは独立したカラム）
- **パートナーの累計未払い合計（3種合算、`Agency::totalPendingPayout()`）が¥1,000未満の場合、そのパートナーの未払い分は一覧の支払対象から除外され「繰り越し予定」に回る**（支払済みの記録は除外されず、取り消しも従来通り可能）
- `Agency::carryOverSummary(int $threshold = 1000)`が繰り越し対象パートナー一覧と合計額を返す（支払い管理・ダッシュボードの両方から呼ばれる共通ロジック）
- ダッシュボードにも「繰り越し予定合計」カードあり（「利益」カードの隣、月フィルタに関係なく常に現在の状態を表示）
- **運用インパクトの注意**: 2026-07-19時点で契約同意（3文書）を提出済みのパートナーは244件中1件のみ。本番公開後、既存パートナーの大多数は次回ログイン時に案件一覧・紹介機能が使えなくなり「追加情報のご入力」への誘導が発生する（審査制導入時の意図通りだが、影響範囲は大きい）

## LINE/LIFF連携（申し込みフォーム、`public/apply/show.blade.php`）

**設計方針**: LIFFログイン画面のような専用フローは作らず、通常の申し込みフォーム1枚に集約。LINEとの連携（ユーザーのLINE ID取得・友だち状態確認）は**「案内を受け取る」ボタンを押した瞬間にだけ**行う（ページを開いた時点では何もLIFF関連の処理をしない＝フォームは常に即表示）。

### 送信時の流れ（`liffReady = liff.init()`は事前に非同期で開始しておく）

1. ボタン押下 → `liffReady`解決を待ち、`liff.isLoggedIn()`をチェック
2. **未ログインの場合**: 入力済みの名前・フリガナ・メールアドレスを**URLクエリパラメータ**に載せ（`tsn_resume=1&name=...`等）、`https://liff.line.me/{LIFF_ID}?from=` + そのパス全体をURLエンコードしたもの、へ`window.location.href`で遷移
3. LINEがログイン処理後、`from`で指定した通りのURL（クエリ含む）にそのまま戻ってくる（アクセストークン等は`#`以降のフラグメントとして付与されるので、こちらのクエリパラメータ部分には影響しない）
4. 戻り先ページで、URLクエリの`tsn_resume`を見て入力内容を復元し、`resumingSubmit=true`として`requestSubmit()`を自動発火 → 今度は`liff.isLoggedIn()`がtrueになっているので、`liff.getProfile()`/`liff.getFriendship()`を取得してから実際にサーバーへPOST
5. **既にログイン済みの場合**（２回目の申込みや、别のLIFFセッションが生きている場合）: 上記2〜4を経由せず、その場で`getProfile()`/`getFriendship()`を取って即座に送信

### ハマったポイント（時系列で得た教訓）

- **`sessionStorage`は使えない**: `liff.line.me`経由でログインすると、LINEアプリの**中の**専用LIFFブラウザとして開き直される（`liff.isInClient()`が`false`→`true`に変わる）。これは別のブラウジングコンテキスト扱いになるため、`sessionStorage`は引き継がれない。入力内容は**URLクエリパラメータ**に乗せて渡すこと（`sessionStorage`ではなく）
- **`liff.login()`の`redirectUri`は信用しない**: 素のURL（`/apply/{token}`のようなプレーンなリンクをLINEのトーク上でタップして開く形）は`isInClient: false`（LIFFの正規起動ではなく外部ブラウザ扱い）になり、この状態では`liff.login({ redirectUri: ... })`を指定しても無視されて**必ずエンドポイントURLちょうどに戻る**（LINE公式ドキュメントにも「動作は保証されない」と明記あり）。確実にログイン→元のページに戻したい場合は、SDKの`liff.login()`ではなく、**素の`window.location.href = 'https://liff.line.me/{LIFF_ID}?from=...'`遷移**を使うこと（これは`isInClient: true`の正規LIFF起動になり、`from`で指定した通りのURLに確実に戻ってくる）
- **エンドポイントURLは実在するルートにする**: `liff.login()`はエンドポイントURLちょうどに戻ることがあるため、そのURLは「トークン必須の`/apply/{token}`」のような動的ルートではなく、パラメータ無しでも200を返す実在ルートにする必要がある（`Route::get('apply', ...)`を追加して対応。中身は「元の画面に戻ってください」という中継用の簡素な文言のみ）
- **エンドポイントURLの着地先は対象読者に注意**: 最初トップページ（`/`）をエンドポイントURLにしていたが、`/`は元々パートナー登録LP（`/agency/register`）にリダイレクトする設定だったため、**申し込みユーザーが誤ってパートナー向けLPに着地する**事故になった。ユーザー向けの中継先は必ず専用ページ（`public.line_login_complete`ビュー）にすること
- **「ボットリンク」設定を忘れずに**: LIFFアプリをLINE Loginチャンネルで作成した場合、そのチャンネルとMessaging APIチャンネル（公式アカウント）を**明示的に紐付けないと**`liff.getFriendship()`が`There is no login bot linked to this channel.`エラーで失敗する。LINE Developersコンソールで、LINE Loginチャンネル側の設定にある「Linked LINE Official Account（ボットリンク）」で紐付けが必要
- **原因調査は最終的に「サーバーログに直接書く」方式が有効だった**: 実機LINEでしか再現しない不具合を都度ユーザーに言葉で説明してもらうのは非効率・不正確になりがち。`navigator.sendBeacon()`で自前の`/debug-log`エンドポイント（CSRF除外、Laravelログに書くだけ）に各ステップを送り、開発側が直接ログを`tail`する方式に切り替えたところ、一度で正確な原因（上記のボットリンク未設定）まで特定できた。同種の「外部アプリ経由でしか再現しない」不具合はこの手法を早めに使うとよい
- **`LineWebhookController`（follow/unfollowイベント）は実際に使われている**: 友だち未追加のままフォーム送信した場合、後から友だち追加された時点で`handleFollow()`が保留中の`Inquiry`（`guidance_sent_at`が空のもの）を検知し自動で案内メッセージを送る、という実装が既にあった。一見DBフィールドの読み書きだけに見えても、メソッド全体を読まずに「未使用」と判断しないこと

## パートナー側LINE連携（`Agency\LineConnectionController`、ログイン済みユーザーがLIFF経由で連携する場合）

**申し込みフォームの「未ログインゲスト」ケースとは別の問題がある**: 既にマイページにログイン済みのパートナーが「LINE連携する」ボタンを押して`https://liff.line.me/{LIFF_ID}?from=...`に遷移すると、上記の申し込みフォームと同様に**LINEアプリ内の別ブラウジングコンテキストとして開き直される**。この時、元のブラウザのログインセッションCookieは引き継がれない（`sessionStorage`が引き継がれないのと同じ原理）。そのため、連携ボタンを押した後の着地ページが`auth:agency`ミドルウェア配下のルートだと、LINEから戻った瞬間にログイン画面へ飛ばされてしまい、連携そのものが成立しない。

**解決策（採用した設計）**: ログイン中のセッションに依存せず、使い捨てトークンだけでパートナーを特定する方式にした。
1. 連携ボタンを描画する時点（まだ元のブラウザセッション内）で、`encrypt(['agency_id' => ..., 'expires_at' => ...])`で15分限定の署名付きトークンを生成し、LIFFの`from`パラメータに埋め込む（`partials/agency_line_connect_button.blade.php`）
2. LINEから戻ってくる着地ページ（`agency/line-connection/callback`のGET/POST）は`auth:agency`の外に置き、ログイン状態を一切前提にしない
3. 着地ページで`liff.getProfile()`取得後、トークン＋LINEプロフィールをPOSTし、コントローラ側はトークンを復号してAgencyを特定→`line_uid`を保存→`Auth::guard('agency')->login($agency)`でその場（LIFF内ブラウザ）に新しいセッションを張ってから`/agency/home`へリダイレクトする
4. 連携解除（`destroy`）はマイページ内の通常フォーム送信のみで完結するため、LIFF遷移が発生せずこの問題の対象外（`auth:agency`のままでよい）

**注意**: 同様に「ログイン済みユーザーがLIFFを経由する」新機能を作る場合は、着地ページを認証必須ルートにしないこと。認証が必要な処理は必ずトークン等セッション非依存の手段で本人確認すること。

## 開発環境の注意点

- Laragonのnode/npmはPATHに無いため、ビルド時は`export PATH="/c/laragon/bin/nodejs/node-v22:$PATH"`が必要
- Windows上のcurlで日本語を直接argvに渡すと文字化けする（Shift-JIS系に化ける）。日本語を含むPOSTテストは`http_build_query()`で事前にURLエンコードしたASCII文字列を`--data`で渡すか、PHPスクリプトファイル経由でDB操作すること
- PowerShellの`Get-Content`/`Set-Content`はPHP/Bladeファイルの読み書きに使わない
- `public/build`（Viteのビルド成果物）はSTGにnode/npmが無いため`.gitignore`から外してGit管理している。**Blade側でTailwindクラスを新規追加・変更した際は`npm run build`を忘れずに実行してからコミットすること**（2026-07-19に一度、複数画面分のビルド忘れが発覚し再ビルドで修正した）
- ローカルでのHTTPテスト（`php artisan serve`）は、ログイン直後に間を置かず次のリクエストを送るとSQLiteセッションの書き込みが間に合わず401/302になることがある（`sleep(1)`程度の間隔を空けると安定する）
- **Bladeでのチェーンしたプロパティアクセスは`??`必須**: `{{ $model->relation->field }}`のように`??`フォールバックを挟まずに書くと、`relation`がnullの行が来た瞬間に「Attempt to read property on null」の警告が発生し、本番相当のリクエストパイプラインでは`ErrorException`に昇格して500になる（`agency/inquiries/index.blade.php`の`$inquiry->lineUser->display_name`で実際に発生。合計成果反映機能がLINEユーザー無しの`Inquiry`を作るようになったことで新たに顕在化した）。管理画面側の同等箇所は`?? $inquiry->legacy_line_display_name`のフォールバックが最初から入っており無事だった。新しいnull許容の関連付けを増やす変更をするときは、既存ビューでその関連を無条件にチェーンしている箇所がないか確認すること

## 未着手・今後の検討事項

- mkgrp.bizの本番ドメイン（`tsunagu.mkgrp.biz`）への切り替え・デプロイ方法は未検討（BIMONIはSTG自動デプロイ・本番は確認ありのGitベースのフローを使っている）
- `legal_documents`のシーダー内容はプレースホルダーテキストのため、本番公開前に実際の利用規約・プライバシーポリシー・パートナー業務委託契約書の文言に差し替えが必要
- 旧問い合わせデータのインポート（`Project.legacy_names`を使う想定）は未着手
- 共創報酬（`CollaborationReward`）は`client_name`の文字列一致でパートナーに紐付いており、同じ取引先名を別パートナーが別案件で使うと報酬が二重計上される可能性がある（既存の設計、今回のスコープ外）
