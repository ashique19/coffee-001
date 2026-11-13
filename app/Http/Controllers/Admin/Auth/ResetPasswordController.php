<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\Auth\StaffPasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request, Tenant $tenant, string $token): View
    {
        return view('tenant.auth.reset-password', [
            'tenant' => $tenant,
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function store(Request $request, Tenant $tenant, StaffPasswordReset $resets): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $resets->resetPassword(
            $tenant,
            $validated['email'],
            $validated['token'],
            $validated['password'],
        )) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.token_invalid')]);
        }

        return redirect()
            ->route('admin.login', $tenant)
            ->with('status', __('auth.password_reset'));
    }
}
