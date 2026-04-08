<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\SendOtpRequest;
use App\Http\Requests\Document\VerifyOtpRequest;
use App\Services\DocumentAccessService;
use Illuminate\Http\Request;

class DocumentShareController extends Controller
{
    public function __construct(private DocumentAccessService $service) {}

    public function showEmailForm(string $token)
    {
        $document = $this->service->resolveDocument($token);
        abort_if(!$document, 404);

        return view('doc.email', compact('document', 'token'));
    }

    public function sendOtp(SendOtpRequest $request, string $token)
    {
        $document = $this->service->resolveDocument($token);
        abort_if(!$document, 404);

        if ($this->service->isRateLimited($document, $request->email)) {
            $seconds = $this->service->rateLimitAvailableIn($document, $request->email);
            return back()->withErrors(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $this->service->sendOtp($document, $request->email);

        return redirect()->route('doc.otp', $token)
            ->with('otp_email', $request->email)
            ->with('status', 'OTP sent to your email.');
    }

    public function showOtpForm(string $token)
    {
        $document = $this->service->resolveDocument($token);
        abort_if(!$document, 404);

        $email = session('otp_email');
        if (!$email) {
            return redirect()->route('doc.email', $token);
        }

        return view('doc.otp', compact('document', 'token', 'email'));
    }

    public function verifyOtp(VerifyOtpRequest $request, string $token)
    {
        $document = $this->service->resolveDocument($token);
        abort_if(!$document, 404);

        if (!$this->service->verifyOtp($document, $request->email, $request->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $this->service->storeSession($request, $document, $request->email);

        return redirect()->route('doc.view', $token);
    }

    public function viewDocument(Request $request, string $token)
    {
        $document = $this->service->resolveDocument($token);
        abort_if(!$document, 404);

        if (!$this->service->hasValidSession($request, $document)) {
            return redirect()->route('doc.email', $token);
        }

        $this->service->logView($request, $document);

        return view('doc.view', compact('document'));
    }
}
