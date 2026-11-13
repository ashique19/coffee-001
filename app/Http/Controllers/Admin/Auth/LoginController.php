<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\BusinessSettingUser;
use App\Support\TenantLandingRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Tenant $tenant): View
    {
        return view('tenant.auth.login', ['tenant' => $tenant]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = StaffUser::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('pos.login_failed'),
            ]);
        }

        Auth::guard('tenant')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(TenantLandingRoute::forUser($tenant, $user));
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login', ['tenant' => $tenant]);
    }
}
