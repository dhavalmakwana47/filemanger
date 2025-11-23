<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        // Check if 'active_company' session variable is not set
        if (!session()->has('active_company')) {
            // Get the current user's first company ID and set it in the session
            $firstCompanyId = current_user()->companies()->first()->id ?? null; // Use null coalescing to prevent errors if there are no companies

            if ($firstCompanyId) { // Ensure that $firstCompanyId is not null before setting it in the session
                session(['active_company' => $firstCompanyId]);
            }
            // Log the user login action
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "User " . (Auth::user()->name ?? 'Unknown') . " logged in"
            ]);
            $request->session()->forget('nda_agreement');


        }
            $user = Auth::user();
            if(!$user->two_factor_enabled){
                return redirect()->intended(route('dashboard', absolute: false));
            }

            // Check if user has 2FA enabled (optional – add a column later if needed)
            // For now, force 2FA for everyone
            $user->sendOtp();

            Auth::logout(); // Important: log out until OTP verified

            $request->session()->put('2fa_user_id', $user->id);

            return redirect()->route('2fa.show');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Remove NDA agreement session
        $request->session()->forget('nda_agreement');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
