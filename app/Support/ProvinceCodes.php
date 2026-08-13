<?php

namespace App\Support;

/**
 * Shared Canadian province name/code normalization, used both when saving
 * a PlatformTaxRate (so it's always stored as a clean 2-letter code) and
 * when looking one up for a business (so "Ontario", "ontario", and "ON"
 * all resolve the same way).
 */
class ProvinceCodes
{
    private const CODES = [
        'AB' => 'AB', 'ALBERTA' => 'AB',
        'BC' => 'BC', 'BRITISH COLUMBIA' => 'BC',
        'MB' => 'MB', 'MANITOBA' => 'MB',
        'NB' => 'NB', 'NEW BRUNSWICK' => 'NB',
        'NL' => 'NL', 'NEWFOUNDLAND AND LABRADOR' => 'NL', 'NEWFOUNDLAND' => 'NL',
        'NS' => 'NS', 'NOVA SCOTIA' => 'NS',
        'NT' => 'NT', 'NORTHWEST TERRITORIES' => 'NT',
        'NU' => 'NU', 'NUNAVUT' => 'NU',
        'ON' => 'ON', 'ONTARIO' => 'ON',
        'PE' => 'PE', 'PRINCE EDWARD ISLAND' => 'PE',
        'QC' => 'QC', 'QUEBEC' => 'QC', 'QUÉBEC' => 'QC',
        'SK' => 'SK', 'SASKATCHEWAN' => 'SK',
        'YT' => 'YT', 'YUKON' => 'YT',
    ];

    /**
     * Normalizes any recognizable spelling of a Canadian province into its
     * 2-letter code, or null if blank/unrecognized.
     */
    public static function normalize(?string $raw): ?string
    {
        $key = strtoupper(trim($raw ?? ''));

        return self::CODES[$key] ?? null;
    }

    /**
     * Code => display name, one entry per province/territory (no aliases),
     * for populating a dropdown as e.g. "Ontario (ON)".
     */
    public static function options(): array
    {
        return [
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NS' => 'Nova Scotia',
            'NT' => 'Northwest Territories',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon',
        ];
    }
}
