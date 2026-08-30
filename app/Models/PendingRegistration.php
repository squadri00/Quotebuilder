<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A registration that hasn't been paid for yet. Holds everything needed
 * to create the real Business + User once Stripe confirms payment
 * (see Stripe\WebhookController::finalizePendingRegistration) — nothing
 * is created up front, so an abandoned checkout never leaves behind a
 * usable, unpaid account.
 */
class PendingRegistration extends Model
{
    protected $fillable = [
        'token',
        'name',
        'company_name',
        'email',
        'password',
        'template_business_id',
        'template_business_ids',
        'product_ids',
        'industry_id',
        'country',
        'state_province',
        'plan_id',
        'affiliate_code',
        'otp_code',
        'otp_expires_at',
        'business_id',
    ];

    protected function casts(): array
    {
        return [
            'template_business_ids' => 'array',
            'product_ids' => 'array',
            'otp_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PendingRegistration $pending) {
            $pending->token ??= (string) Str::uuid();
        });
    }

    /**
     * Generates a fresh 6-digit code, stores it hashed (same as a
     * password — a database leak shouldn't hand out live codes), and
     * returns the plaintext so the caller can email it. Used on the
     * free-plan signup path in place of Stripe's own identity check.
     */
    public function issueOtp(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        return $code;
    }

    public function otpIsExpired(): bool
    {
        return ! $this->otp_expires_at || now()->greaterThan($this->otp_expires_at);
    }

    public function otpMatches(string $code): bool
    {
        return $this->otp_code && Hash::check($code, $this->otp_code);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isFinalized(): bool
    {
        return $this->business_id !== null;
    }

    /**
     * Creates the real Business + User from this pending record — used by
     * the free-plan OTP flow once the code checks out. Mirrors what
     * Stripe\WebhookController does for paid plans after payment
     * confirms, just triggered by email verification instead.
     */
    public function finalizeToBusiness(): User
    {
        return DB::transaction(function () {
            $business = Business::create([
                'name' => $this->company_name,
                'country' => $this->country,
                'state_province' => $this->state_province,
                'plan_id' => $this->plan_id,
                'quotation_disclaimer' => Business::defaultQuotationDisclaimer(),
            ]);

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'business_id' => $business->id,
            ]);

            $this->update(['business_id' => $business->id]);

            return $user;
        });
    }
}
