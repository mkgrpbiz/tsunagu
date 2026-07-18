# TSUNAGU 仕様まとめ（2026-07-18時点）

パートナー（旧・代理店）が案件を紹介し、紹介報酬・共創報酬を得られる審査制のビジネスプラットフォーム。Laravel 13 + Blade + SQLite。まだGitリポジトリ化されていない（`.git`なし）。

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
  - カード単位のブロック: `Agency\HomeController`が`$restricted`フラグを計算し、`partials/home_block.blade.php`内でグレーアウト表示（実際の紹介URL・コードはHTMLに出力しない）
- 管理画面の「パートナー」詳細画面で承認/否認/利用停止/審査中へ戻す操作、`AgencyStatusHistory`に遷移履歴を記録
- 既存パートナーは移行時に一律`approved`済み（審査中に巻き込まれない）

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

## 開発環境の注意点

- Laragonのnode/npmはPATHに無いため、ビルド時は`export PATH="/c/laragon/bin/nodejs/node-v22:$PATH"`が必要
- Windows上のcurlで日本語を直接argvに渡すと文字化けする（Shift-JIS系に化ける）。日本語を含むPOSTテストは`http_build_query()`で事前にURLエンコードしたASCII文字列を`--data`で渡すか、PHPスクリプトファイル経由でDB操作すること
- PowerShellの`Get-Content`/`Set-Content`はPHP/Bladeファイルの読み書きに使わない

## 未着手・今後の検討事項

- Gitリポジトリ化されていない（`git init`未実施）
- mkgrp.bizのサブドメインへのデプロイ方法は未検討（BIMONIはSTG自動デプロイ・本番は確認ありのGitベースのフローを使っている）
- `legal_documents`のシーダー内容はプレースホルダーテキストのため、本番公開前に実際の利用規約・プライバシーポリシー・パートナー業務委託契約書の文言に差し替えが必要
