# TSUNAGU 仕様まとめ（2026-07-20時点）

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

## 案件（Project）の紹介者

- `Project.referrer_agency_id`の選択肢は**`is_collaboration_partner=true`のパートナーのみ**（検索ボックス付きプルダウン、外部ライブラリなしのvanilla JS）
- 編集画面では、既に設定されている紹介者が共創パートナーでなくなっていても選択肢から消えないよう救済表示する

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

## パートナー着金・支払いページ（`agency/contracts`）

- 3セクション: 紹介報酬（自分の着金、1行=1件）／パートナー10%（紹介先パートナー×支払予定日ごとに件数・合計額を集計した行）／共創パートナー30%（取引先ごとに案件数・着金数・合計額を集計、**承認済みのもののみ**表示）
- ページ上部に支払いサイクルの案内文（月末締め翌月5日払い、¥1,000未満は繰り越し）
- 「繰り越し報酬」表示は**累計（全期間）の未払い合計が¥1,000未満の場合、その全額**（`Agency::totalPendingPayout()`が0円になるまで自然に繰り越り続ける仕組みで、繰り越し専用のDBカラムは無い）

## 支払い管理（`admin/payments`）と繰り越し予定

- 4ブロック構成: 紹介報酬（`Contract`）／パートナー10%（`ReferralCommission`）／共創パートナー30%（`CollaborationReward`、承認済みのみ）／繰り越し予定
- `CollaborationReward`にも`payment_status`/`payment_due_date`/`paid_at`を追加し、他の2種と同じ支払済み/未払いの管理ができるようになった（承認待ち/承認の`status`とは独立したカラム）
- **パートナーの累計未払い合計（3種合算、`Agency::totalPendingPayout()`）が¥1,000未満の場合、そのパートナーの未払い分は上3ブロックの支払対象一覧から除外され「繰り越し予定」に回る**（支払済み済みの記録は除外されず、取り消しも従来通り可能）
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

## 開発環境の注意点

- Laragonのnode/npmはPATHに無いため、ビルド時は`export PATH="/c/laragon/bin/nodejs/node-v22:$PATH"`が必要
- Windows上のcurlで日本語を直接argvに渡すと文字化けする（Shift-JIS系に化ける）。日本語を含むPOSTテストは`http_build_query()`で事前にURLエンコードしたASCII文字列を`--data`で渡すか、PHPスクリプトファイル経由でDB操作すること
- PowerShellの`Get-Content`/`Set-Content`はPHP/Bladeファイルの読み書きに使わない
- `public/build`（Viteのビルド成果物）はSTGにnode/npmが無いため`.gitignore`から外してGit管理している。**Blade側でTailwindクラスを新規追加・変更した際は`npm run build`を忘れずに実行してからコミットすること**（2026-07-19に一度、複数画面分のビルド忘れが発覚し再ビルドで修正した）
- ローカルでのHTTPテスト（`php artisan serve`）は、ログイン直後に間を置かず次のリクエストを送るとSQLiteセッションの書き込みが間に合わず401/302になることがある（`sleep(1)`程度の間隔を空けると安定する）

## 未着手・今後の検討事項

- mkgrp.bizの本番ドメイン（`tsunagu.mkgrp.biz`）への切り替え・デプロイ方法は未検討（BIMONIはSTG自動デプロイ・本番は確認ありのGitベースのフローを使っている）
- `legal_documents`のシーダー内容はプレースホルダーテキストのため、本番公開前に実際の利用規約・プライバシーポリシー・パートナー業務委託契約書の文言に差し替えが必要
- 旧問い合わせデータのインポート（`Project.legacy_names`を使う想定）は未着手
- 共創報酬（`CollaborationReward`）は`client_name`の文字列一致でパートナーに紐付いており、同じ取引先名を別パートナーが別案件で使うと報酬が二重計上される可能性がある（既存の設計、今回のスコープ外）
