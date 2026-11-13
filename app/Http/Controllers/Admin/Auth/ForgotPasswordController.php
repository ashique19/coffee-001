<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\Auth\StaffPasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(Tenant $tenant): View
    {
        return view('tenant.auth.forgot-password', ['tenant' => $tenant]);
    }

    public function store(Request $request, Tenant $tenant, StaffPasswordReset $resets): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $resets->sendResetLink($tenant, $request->string('email')->toString());

        return back()->with('status', __('auth.reset_link_sent'));
    }
}
