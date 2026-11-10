<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForcedPasswordChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForcedPasswordChangeController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->mustChangePassword(), 403);

        return view('auth.forced-password-change');
    }

    public function update(ForcedPasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->route('dashboard')
            ->with('status', __('Password updated. You can continue using HRMS Lite.'));
    }
}
