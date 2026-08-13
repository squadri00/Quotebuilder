<?php

namespace App\Support;

/**
 * The single place that defines what's grantable to a Member beyond
 * product access. Team/Billing are deliberately never in this list — they
 * stay Owner-only, hardcoded, forever (see User::canManageTeam() /
 * canAccessBilling()). To make a new area of the app grantable later, add
 * one entry here and check Auth::user()->hasPermission('the_key') (or
 * apply the 'permission:the_key' middleware) wherever that area is
 * gated — the Team invite/edit forms and validation pick it up
 * automatically.
 */
class TeamPermissions
{
    public const TAX_RATES = 'tax_rates';

    public const BUSINESS_SETTINGS = 'business_settings';

    public const ANNOUNCEMENTS = 'announcements';

    public const SUPPORT = 'support';

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function all(): array
    {
        return [
            self::TAX_RATES => [
                'label' => 'Tax Rates',
                'description' => 'Add, edit, and remove the tax lines charged on quotes.',
            ],
            self::BUSINESS_SETTINGS => [
                'label' => 'Business Settings',
                'description' => 'Update business details and branding (logo, colour).',
            ],
            self::ANNOUNCEMENTS => [
                'label' => 'Announcements',
                'description' => 'View platform announcements sent to this account.',
            ],
            self::SUPPORT => [
                'label' => 'Support',
                'description' => 'Open and reply to support tickets.',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
