<?php

namespace App\Services\Affiliate;

/**
 * Normalises the identity fields of a prospect so "Northside Tire &
 * Auto" / "North Side Tire and Auto" / a shared phone number all collide.
 */
class Normalizer
{
    public static function name(?string $s): string
    {
        $s = strtolower(trim((string) $s));
        $s = preg_replace('/&/', ' and ', $s);
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        $s = preg_replace(
            '/\b(the|inc|incorporated|llc|l l c|ltd|limited|corp|corporation|co|company|'
            . 'group|holdings|enterprises|services|service|auto|automotive|garage|motors)\b/',
            ' ',
            $s
        );
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    public static function phone(?string $s): string
    {
        $digits = preg_replace('/\D+/', '', (string) $s);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    public static function email(?string $s): string
    {
        $s = strtolower(trim((string) $s));
        return filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : '';
    }

    public static function domain(?string $s): string
    {
        $s = strtolower(trim((string) $s));
        if ($s === '') {
            return '';
        }
        if (str_contains($s, '@')) {
            $s = substr($s, strpos($s, '@') + 1);
        }
        $s = preg_replace('#^[a-z]+://#', '', $s);
        $s = preg_replace('#^www\.#', '', $s);
        $s = explode('/', $s)[0];
        $s = explode('?', $s)[0];
        $s = trim($s, '. ');

        $free = ['gmail.com', 'googlemail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
                 'live.com', 'aol.com', 'icloud.com', 'proton.me', 'protonmail.com', 'mail.com'];
        if (in_array($s, $free, true)) {
            return '';
        }

        return preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $s) ? $s : '';
    }

    /**
     * @param  array{company_name?:string,phone?:string,email?:string,website?:string}  $in
     * @return array{company_name_norm:string,phone_norm:string,email_norm:string,domain_norm:string}
     */
    public static function keys(array $in): array
    {
        $email = static::email($in['email'] ?? '');
        $domain = static::domain($in['website'] ?? '');
        if ($domain === '' && $email !== '') {
            $domain = static::domain($email);
        }

        return [
            'company_name_norm' => static::name($in['company_name'] ?? ''),
            'phone_norm' => static::phone($in['phone'] ?? ''),
            'email_norm' => $email,
            'domain_norm' => $domain,
        ];
    }
}
