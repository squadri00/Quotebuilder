<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Checks a Cloudflare Turnstile response token against Cloudflare's own
 * verification endpoint — the server-side half of the widget rendered on
 * public quote forms. See PublicQuoteController::store() for the other
 * half (the honeypot field) and how both gate a submission before any
 * Quote/Customer row is created.
 */
class TurnstileVerifier
{
    /**
     * True whenever Turnstile isn't configured at all (nothing to check
     * against — every submission passes through, same as before this
     * feature existed) or the token genuinely checks out with Cloudflare.
     * False for a missing/invalid/expired token, or if Cloudflare's own
     * endpoint is unreachable — a real submission attempt should still
     * usually have gotten a token, so failing closed here favors blocking
     * an edge case over silently letting bots back in.
     */
    public function passes(?string $token, ?string $ip): bool
    {
        $secret = PlatformSetting::get()->turnstile_secret_key;

        if (! $secret) {
            return true;
        }

        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Turnstile verification request failed: '.$e->getMessage());

            return false;
        }

        return (bool) $response->json('success');
    }
}
