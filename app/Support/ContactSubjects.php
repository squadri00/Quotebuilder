<?php

namespace App\Support;

/**
 * Curated subject options for the public Contact form — keeps the
 * dropdown, server-side validation, and the email subject line in
 * sync from one source instead of three copies of the same list.
 */
class ContactSubjects
{
    private const SUBJECTS = [
        'general' => 'General Question',
        'sales' => 'Sales & Pricing',
        'support' => 'Technical Support',
        'billing' => 'Billing Question',
        'feature_request' => 'Feature Request',
        'partnership' => 'Partnership',
        'other' => 'Other',
    ];

    /**
     * @return array<string, string> value => label, for the <select>
     */
    public static function options(): array
    {
        return self::SUBJECTS;
    }

    public static function keys(): array
    {
        return array_keys(self::SUBJECTS);
    }

    public static function label(string $key): string
    {
        return self::SUBJECTS[$key] ?? self::SUBJECTS['other'];
    }
}
