<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagerController extends Controller
{
    private function requireAdmin(): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'この操作は管理者のみ実行できます。');
        }
    }

    public static function menuKeys(): array
    {
        return [
            'dashboard' => 'ダッシュボード',
            'projects' => '案件',
            'categories' => 'カテゴリー',
            'agencies' => 'パートナー',
            'collaboration_partners' => '共創パートナー',
            'inquiries' => '問い合わせ',
            'deposit_links' => '着金紐付け',
            'payments' => '支払い',
            'announcements' => 'お知らせ',
            'collaboration_referrals' => '共創紹介',
            'collaboration_rewards' => '共創報酬管理',
            'legal_documents' => '契約管理',
            'home' => 'ホーム',
            'landing_page_content' => 'LP',
        ];
    }

    public function index(): View
    {
        $this->requireAdmin();

        return view('admin.admins.index', [
            'admins' => User::orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        $this->requireAdmin();

        return view('admin.admins.create', [
            'menuKeys' => self::menuKeys(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'accessible_menus' => ['nullable', 'array'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => 'pass1234',
            'role' => $data['role'],
            'accessible_menus' => $data['role'] === 'operator' ? ($data['accessible_menus'] ?? []) : null,
        ]);

        return redirect()->route('admin.admins.index')->with('status', '管理者を追加しました。初期パスワード: pass1234');
    }

    public function edit(User $admin): View
    {
        $this->requireAdmin();

        return view('admin.admins.edit', [
            'admin' => $admin,
            'menuKeys' => self::menuKeys(),
        ]);
    }

    public function update(Request $request, User $admin): RedirectResponse
    {
        $this->requireAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($admin)],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'accessible_menus' => ['nullable', 'array'],
        ]);

        $admin->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'accessible_menus' => $data['role'] === 'operator' ? ($data['accessible_menus'] ?? []) : null,
        ]);

        return redirect()->route('admin.admins.index')->with('status', '更新しました。');
    }

    public function destroy(User $admin): RedirectResponse
    {
        $this->requireAdmin();

        if ($admin->id === Auth::id()) {
            return back()->with('error', '自分自身は削除できません。');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('status', '削除しました。');
    }

    public function resetPassword(User $admin): RedirectResponse
    {
        $this->requireAdmin();

        $admin->update(['password' => 'pass1234']);

        return back()->with('status', 'パスワードを pass1234 にリセットしました。');
    }
}
