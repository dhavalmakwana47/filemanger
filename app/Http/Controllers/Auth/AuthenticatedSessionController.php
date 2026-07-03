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

        $user = Auth::user();

        addUserAction([
            'user_id' => $user->id,
            'action'  => "User {$user->name} logged in",
        ]);
        $request->session()->forget('nda_agreement');

        if (! $user->two_factor_enabled) {
            return $this->postLoginRedirect($user);
        }

        $user->sendOtp();
        Auth::logout();
        $request->session()->put('2fa_user_id', $user->id);

        return redirect()->route('2fa.show');
    }

    private function postLoginRedirect($user): RedirectResponse
    {
        $companies = $user->companies()->get();

        if ($companies->count() > 1) {
            return redirect()->route('select-company.show');
        }

        if ($companies->count() === 1) {
            session(['active_company' => $companies->first()->id]);
        }

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
