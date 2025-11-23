<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function show()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        return view('auth.two-factor', compact('user'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $otp = UserOtp::where('user_id', $userId)
            ->where('code', $request->code)
            ->first();

        if ($otp && !$otp->isExpired()) {
            $user = \App\Models\User::find($userId);
            Auth::login($user);

            // Clean up
            $otp->delete();
            $request->session()->forget('2fa_user_id');

            // Now run your original post-login logic
            if (!session()->has('active_company')) {
                $firstCompanyId = $user->companies()->first()?->id;
                if ($firstCompanyId) {
                    session(['active_company' => $firstCompanyId]);
                }
                addUserAction([
                    'user_id' => $user->id,
                    'action' => "User {$user->name} logged in"
                ]);
                $request->session()->forget('nda_agreement');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['code' => 'Invalid or expired code']);
    }

    public function resend(Request $request)
    {
        $userId = session('2fa_user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            $user?->sendOtp();
        }

        return back()->with('status', 'New code sent!');
    }
}
