<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentOtp;
use App\Models\DocumentView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class DocumentAccessService
{
    private const SESSION_KEY   = 'document_access';
    private const OTP_TTL_MIN   = 10;
    private const SESSION_TTL_MIN = 30;

    public function resolveDocument(string $token): ?Document
    {
        return Document::where('share_token', $token)->first();
    }

    public function sendOtp(Document $document, string $email): bool
    {
        $rateLimiterKey = 'doc-otp:' . $document->id . ':' . $email;

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 3)) {
            return false;
        }

        RateLimiter::hit($rateLimiterKey, 60);

        // Invalidate previous OTPs for this doc+email
        DocumentOtp::where('document_id', $document->id)
            ->where('email', $email)
            ->whereNull('verified_at')
            ->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DocumentOtp::create([
            'document_id' => $document->id,
            'email'       => $email,
            'otp'         => $otp,
            'expires_at'  => now()->addMinutes(self::OTP_TTL_MIN),
        ]);

        Mail::send([], [], function ($message) use ($email, $otp, $document) {
            $message->to($email)
                ->subject('Your OTP to view: ' . $document->title)
                ->html(
                    '<p>Your one-time password to view the document <strong>' . e($document->title) . '</strong> is:</p>'
                    . '<h2 style="letter-spacing:6px">' . $otp . '</h2>'
                    . '<p>This code expires in ' . self::OTP_TTL_MIN . ' minutes.</p>'
                );
        });

        return true;
    }

    public function verifyOtp(Document $document, string $email, string $otp): bool
    {
        $record = DocumentOtp::where('document_id', $document->id)
            ->where('email', $email)
            ->where('otp', $otp)
            ->whereNull('verified_at')
            ->first();

        if (!$record || $record->isExpired()) {
            return false;
        }

        $record->update(['verified_at' => now()]);

        DocumentView::create([
            'document_id' => $document->id,
            'email'       => $email,
            'verified_at' => now(),
        ]);

        return true;
    }

    public function storeSession(Request $request, Document $document, string $email): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'document_id' => $document->id,
            'email'       => $email,
            'verified'    => true,
            'expires_at'  => now()->addMinutes(self::SESSION_TTL_MIN)->timestamp,
        ]);
    }

    public function hasValidSession(Request $request, Document $document): bool
    {
        $access = $request->session()->get(self::SESSION_KEY);

        return $access
            && $access['verified'] === true
            && $access['document_id'] === $document->id
            && $access['expires_at'] > now()->timestamp;
    }

    public function logView(Request $request, Document $document): void
    {
        $email = $request->session()->get(self::SESSION_KEY . '.email');

        DocumentView::create([
            'document_id' => $document->id,
            'email'       => $email,
            'viewed_at'   => now(),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);
    }

    public function isRateLimited(Document $document, string $email): bool
    {
        return RateLimiter::tooManyAttempts('doc-otp:' . $document->id . ':' . $email, 3);
    }

    public function rateLimitAvailableIn(Document $document, string $email): int
    {
        return RateLimiter::availableIn('doc-otp:' . $document->id . ':' . $email);
    }
}
