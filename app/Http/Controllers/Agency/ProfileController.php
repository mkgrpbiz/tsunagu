<?php

namespace App\Http\Controllers\Agency;

use App\Enums\BankAccountType;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $agency = Auth::guard('agency')->user();

        return view('agency.profile.edit', [
            'agency' => $agency,
            'genders' => Gender::cases(),
            'bankAccountTypes' => BankAccountType::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Agency $agency */
        $agency = Auth::guard('agency')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'prefecture' => ['required', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('agencies', 'email')->ignore($agency)],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch_name' => ['nullable', 'string', 'max:255'],
            'bank_account_type' => ['nullable', Rule::enum(BankAccountType::class)],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $agency->password)) {
                throw ValidationException::withMessages([
                    'current_password' => '現在のパスワードが正しくありません。',
                ]);
            }

            $data['must_change_password'] = false;
        } else {
            unset($data['password']);
        }

        unset($data['current_password']);

        $agency->update($data);

        return redirect()->route('agency.profile.edit')->with('status', 'プロフィールを更新しました。');
    }
}
